<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\Authentication\AuthenticationService;
use Laminas\View\Model\ViewModel; // Add this import
use Application\Model\BorrowModel;
use Application\Model\StudentModel;
use Application\Model\BookModel;
use Application\Model\SettingsModel;
use Application\Model\LogModel;
use Application\Model\PaymentModel;
use Application\Model\UserModel;

class BorrowController extends AbstractActionController
{
    private $borrowModel;
    private $studentModel;
    private $bookModel;
    private $settingsModel;
    private $logModel;
    private $paymentModel;
    private $userModel;
    private $authService;

    public function __construct(
        BorrowModel $borrowModel,
        StudentModel $studentModel,
        BookModel $bookModel,
        SettingsModel $settingsModel,
        LogModel $logModel,
        PaymentModel $paymentModel,
        UserModel $userModel,
        AuthenticationService $authService
    ) {
        $this->borrowModel = $borrowModel;
        $this->studentModel = $studentModel;
        $this->bookModel = $bookModel;
        $this->settingsModel = $settingsModel;
        $this->logModel = $logModel;
        $this->paymentModel = $paymentModel;
        $this->userModel = $userModel;
        $this->authService = $authService;
    }

    private function checkAccess(): bool
    {
        $sessionContainer = new Container('AuthNamespace');
        $identity = $this->authService->getIdentity() ?? $sessionContainer->identity;

        if (!$identity) {
            $this->flashMessenger()->addMessage('Please log in to access this page.');
            $this->redirect()->toRoute('login');
            return false;
        }

        $userType = trim($identity['type'] ?? '');
        if ($userType !== 'System User') {
            $this->flashMessenger()->addMessage('Access denied. System users only.');
            $this->redirect()->toRoute('login');
            return false;
        }

        return true;
    }

    private function getIdentity()
    {
        $sessionContainer = new Container('AuthNamespace');
        return $this->authService->getIdentity() ?? $sessionContainer->identity;
    }

    public function indexAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        $borrows = $this->borrowModel->getAllBorrows();
        $penaltyRate = (float) $this->settingsModel->getSetting('penalty_rate');

        return new ViewModel([
            'borrows' => $borrows,
            'penaltyRate' => $penaltyRate,
        ]);
    }

    public function returnAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Borrow ID not provided.');
            return $this->redirect()->toRoute('borrows');
        }

        try {
            $borrow = $this->borrowModel->getBorrowById($id);
            if ($borrow->status === 'Returned') {
                $this->flashMessenger()->addMessage('Book has already been returned.');
                return $this->redirect()->toRoute('borrows');
            }

            $this->borrowModel->returnBook($id);
            $book = $this->bookModel->getBookById($borrow->book_id);
            $this->bookModel->updateBookQuantity($borrow->book_id, $book->quantity + 1);

            $identity = $this->getIdentity();
            $this->logModel->addLog(
                'Book Returned',
                "Borrow ID {$id} returned.",
                $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                $identity['type']
            );

            $this->flashMessenger()->addMessage('Book returned successfully.');
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Error returning book: ' . $e->getMessage());
        }

        return $this->redirect()->toRoute('borrows');
    }

    public function payPenaltyAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Borrow ID not provided.');
            return $this->redirect()->toRoute('borrows');
        }

        try {
            $borrow = $this->borrowModel->getBorrowById($id);
            $penaltyRate = (float) $this->settingsModel->getSetting('penalty_rate');
            $penalty = $borrow->getPenalty($penaltyRate);

            if ($penalty <= 0) {
                $this->flashMessenger()->addMessage('No penalty to pay.');
                return $this->redirect()->toRoute('borrows');
            }

            $this->paymentModel->addPayment([
                'borrow_id' => $borrow->id,
                'student_id' => $borrow->student_id,
                'amount' => $penalty,
                'payment_date' => date('Y-m-d H:i:s'),
            ]);

            $identity = $this->getIdentity();
            $this->logModel->addLog(
                'Penalty Paid',
                "Penalty of \${$penalty} paid for Borrow ID {$id}.",
                $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                $identity['type']
            );

            $this->flashMessenger()->addMessage('Penalty payment recorded successfully.');
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Error recording penalty payment: ' . $e->getMessage());
        }

        return $this->redirect()->toRoute('borrows');
    }
}