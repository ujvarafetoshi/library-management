<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Model\BorrowModel;
use Application\Model\BookModel;
use Application\Model\StudentModel;
use Application\Model\SettingsModel;
use Application\Model\LogModel;
use Application\Model\UserModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\Session\Container;

class IndexController extends AbstractActionController
{
    private $borrowModel;
    private $bookModel;
    private $studentModel;
    private $settingsModel;
    private $logModel;
    private $authService;
    private $userModel;

    public function __construct(
        BorrowModel $borrowModel,
        BookModel $bookModel,
        StudentModel $studentModel,
        SettingsModel $settingsModel,
        LogModel $logModel,
        AuthenticationService $authService,
        UserModel $userModel
    ) {
        $this->borrowModel = $borrowModel;
        $this->bookModel = $bookModel;
        $this->studentModel = $studentModel;
        $this->settingsModel = $settingsModel;
        $this->logModel = $logModel;
        $this->authService = $authService;
        $this->userModel = $userModel;
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function adminDashboardAction()
    {
        $sessionContainer = new Container('AuthNamespace');
        if (!$this->authService->hasIdentity() && !$sessionContainer->identity) {
            error_log('Admin Dashboard: No identity found');
            $this->flashMessenger()->addMessage('Please log in to access the dashboard.');
            return $this->redirect()->toRoute('login');
        }

        $identity = $this->authService->getIdentity() ?? $sessionContainer->identity;
        error_log('Admin Dashboard Identity: ' . print_r($identity, true));
        error_log('Identity Type: ' . gettype($identity));

        $userType = trim($identity['type'] ?? '');
        error_log('Admin Dashboard User Type: ' . $userType);
        if ($userType !== 'System User') {
            error_log('Admin Dashboard: Access denied for user type: ' . $userType);
            $this->flashMessenger()->addMessage('Access denied. System users only.');
            return $this->redirect()->toRoute('login');
        }

        $role = trim($identity['role'] ?? 'Operator');
        $adminEmail = $identity['email'];

        // Fetch dashboard statistics
        $books = $this->bookModel->getAllBooks();
        $borrows = $this->borrowModel->getAllBorrows();
        $students = $this->studentModel->getAllStudents();
        $users = $this->userModel->getAllUsers();
        $totalBooks = count($books);
        $totalBorrows = count($borrows);
        $totalStudents = count($students);
        $totalUsers = count($users);
        $lowStockBooks = $this->bookModel->getLowStockBooks(5);
        $allBorrows = $this->borrowModel->getAllBorrows();
        $overdueBorrows = $this->borrowModel->getOverdueBorrows();
        $penaltyRate = (float) $this->settingsModel->getSetting('penalty_rate');

        return new ViewModel([
            'adminEmail' => $adminEmail,
            'role' => $role,
            'totalBooks' => $totalBooks,
            'totalBorrows' => $totalBorrows,
            'totalStudents' => $totalStudents,
            'totalUsers' => $totalUsers,
            'lowStockBooks' => $lowStockBooks,
            'allBorrows' => $allBorrows,
            'overdueBorrows' => $overdueBorrows,
            'penaltyRate' => $penaltyRate,
        ]);
    }

    public function studentDashboardAction()
    {
        $sessionContainer = new Container('AuthNamespace');
        if (!$this->authService->hasIdentity() && !$sessionContainer->identity) {
            error_log('Student Dashboard: No identity found');
            $this->flashMessenger()->addMessage('Please log in to access the dashboard.');
            return $this->redirect()->toRoute('login');
        }

        $identity = $this->authService->getIdentity() ?? $sessionContainer->identity;
        error_log('Student Dashboard Identity: ' . print_r($identity, true));
        error_log('Identity Type: ' . gettype($identity));

        $userType = trim($identity['type'] ?? '');
        error_log('Student Dashboard User Type: ' . $userType);
        if ($userType !== 'Student') {
            error_log('Student Dashboard: Access denied for user type: ' . $userType);
            $this->flashMessenger()->addMessage('Access denied. Students only.');
            return $this->redirect()->toRoute('login');
        }

        $studentEmail = $identity['email'];
        $student = $this->studentModel->getStudentByEmail($studentEmail);
        if (!$student) {
            $this->flashMessenger()->addMessage('Student not found.');
            return $this->redirect()->toRoute('login');
        }

        // Fetch borrowing history
        $borrowingHistory = $this->borrowModel->getBorrowingHistoryByStudentEmail($studentEmail);

        // Handle search for books
        $searchResults = [];
        $searchTerm = $this->params()->fromQuery('search', '');
        if (!empty($searchTerm)) {
            $searchResults = $this->bookModel->searchBooks($searchTerm);
        }

        // Fetch available books
        $availableBooks = $this->getAvailableBooks();

        // Handle book borrowing and returning
        $request = $this->getRequest();
        if ($request->isPost()) {
            $action = $request->getPost('action');
            if ($action === 'borrow') {
                $bookId = (int) $request->getPost('book_id');
                try {
                    $book = $this->bookModel->getBookById($bookId);
                    if (!$book || $book->quantity <= 0) {
                        throw new \Exception('Book is not available for borrowing.');
                    }

                    // Calculate due date (e.g., 14 days from today)
                    $borrowDate = date('Y-m-d');
                    $borrowingDuration = (int) $this->settingsModel->getSetting('borrowing_duration') ?: 14;
                    $dueDate = date('Y-m-d', strtotime("+$borrowingDuration days"));

                    // Borrow the book
                    $this->borrowModel->borrowBook($student->id, $bookId, $borrowDate, $dueDate);

                    // Decrease book quantity
                    $this->bookModel->updateBookQuantity($bookId, $book->quantity - 1);

                    // Log the action
                    $this->logModel->addLog(
                        'Book Borrowed',
                        "Student ID {$student->id} borrowed book ID {$bookId}.",
                        null,
                        'Student'
                    );

                    $this->flashMessenger()->addMessage('Book borrowed successfully.');
                } catch (\Exception $e) {
                    $this->flashMessenger()->addMessage('Error borrowing book: ' . $e->getMessage());
                }
                return $this->redirect()->toRoute('studentDashboard');
            }

            // Handle book return
            $borrowId = (int) $request->getPost('borrow_id');
            if ($borrowId) {
                try {
                    $borrow = $this->borrowModel->getBorrowById($borrowId);
                    $this->borrowModel->returnBook($borrowId);
                    $this->bookModel->updateBookQuantity($borrow->book_id, $this->bookModel->getBookById($borrow->book_id)->quantity + 1);

                    // Log the action
                    $this->logModel->addLog(
                        'Book Returned',
                        "Student ID {$student->id} returned book ID {$borrow->book_id}.",
                        null,
                        'Student'
                    );

                    $this->flashMessenger()->addMessage('Book returned successfully.');
                } catch (\Exception $e) {
                    $this->flashMessenger()->addMessage('Error returning book: ' . $e->getMessage());
                }
                return $this->redirect()->toRoute('studentDashboard');
            }
        }

        return new ViewModel([
            'borrowingHistory' => $borrowingHistory,
            'searchResults' => $searchResults,
            'searchTerm' => $searchTerm,
            'availableBooks' => $availableBooks,
        ]);
    }

    private function getAvailableBooks(): array
    {
        $availableBooks = $this->bookModel->getAvailableBooks();
        foreach ($availableBooks as $key => $book) {
            $activeBorrows = $this->borrowModel->countActiveBorrowsForBook($book->id);
            $availableQuantity = $book->quantity - $activeBorrows;
            if ($availableQuantity > 0) {
                $book->availableQuantity = $availableQuantity;
            } else {
                // Remove the book from the list if no copies are available
                unset($availableBooks[$key]);
            }
        }

        return array_values($availableBooks);
    }

    public function settingsAction()
    {
        $sessionContainer = new Container('AuthNamespace');
        if (!$this->authService->hasIdentity() && !$sessionContainer->identity) {
            $this->flashMessenger()->addMessage('Please log in to access the dashboard.');
            return $this->redirect()->toRoute('login');
        }

        $identity = $this->authService->getIdentity() ?? $sessionContainer->identity;
        $userType = trim($identity['type'] ?? '');
        if ($userType !== 'System User') {
            $this->flashMessenger()->addMessage('Access denied. System users only.');
            return $this->redirect()->toRoute('login');
        }

        if ($identity['role'] !== 'Super Admin') {
            $this->flashMessenger()->addMessage('Access denied. Super Admins only.');
            return $this->redirect()->toRoute('adminDashboard');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            // Validate input
            $borrowFee = (float) $data['borrow_fee'];
            $penaltyRate = (float) $data['penalty_rate'];
            $borrowingLimit = (int) $data['borrowing_limit'];

            if ($borrowFee < 0) {
                $this->flashMessenger()->addMessage('Borrow fee cannot be negative.');
                return $this->redirect()->toRoute('adminDashboard', ['action' => 'settings']);
            }
            if ($penaltyRate < 0) {
                $this->flashMessenger()->addMessage('Penalty rate cannot be negative.');
                return $this->redirect()->toRoute('adminDashboard', ['action' => 'settings']);
            }
            if ($borrowingLimit <= 0) {
                $this->flashMessenger()->addMessage('Borrowing limit must be greater than 0.');
                return $this->redirect()->toRoute('adminDashboard', ['action' => 'settings']);
            }

            $this->settingsModel->updateSetting('borrow_fee', $borrowFee);
            $this->settingsModel->updateSetting('penalty_rate', $penaltyRate);
            $this->settingsModel->updateSetting('borrowing_limit', $borrowingLimit);

            // Log the settings update
            $this->logModel->addLog(
                'Settings Updated',
                "System settings updated: Borrow Fee = \${$borrowFee}, Penalty Rate = \${$penaltyRate}/day, Borrowing Limit = {$borrowingLimit}.",
                $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                $identity['type']
            );

            $this->flashMessenger()->addMessage('Settings updated successfully.');
            return $this->redirect()->toRoute('adminDashboard', ['action' => 'settings']);
        }

        $settings = [
            'borrow_fee' => $this->settingsModel->getSetting('borrow_fee'),
            'penalty_rate' => $this->settingsModel->getSetting('penalty_rate'),
            'borrowing_limit' => $this->settingsModel->getSetting('borrowing_limit'),
        ];

        return new ViewModel([
            'settings' => $settings,
        ]);
    }

    public function logsAction()
    {
        $sessionContainer = new Container('AuthNamespace');
        if (!$this->authService->hasIdentity() && !$sessionContainer->identity) {
            $this->flashMessenger()->addMessage('Please log in to access the dashboard.');
            return $this->redirect()->toRoute('login');
        }

        $identity = $this->authService->getIdentity() ?? $sessionContainer->identity;
        $userType = trim($identity['type'] ?? '');
        if ($userType !== 'System User') {
            $this->flashMessenger()->addMessage('Access denied. System users only.');
            return $this->redirect()->toRoute('login');
        }

        if ($identity['role'] !== 'Super Admin') {
            $this->flashMessenger()->addMessage('Access denied. Super Admins only.');
            return $this->redirect()->toRoute('adminDashboard');
        }

        $logs = $this->logModel->getAllLogs();

        return new ViewModel([
            'logs' => $logs,
        ]);
    }
}