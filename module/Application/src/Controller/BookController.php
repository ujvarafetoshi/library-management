<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\Authentication\AuthenticationService;
use Laminas\View\Model\ViewModel;
use Application\Model\BookModel;
use Application\Model\LogModel;
use Application\Model\UserModel;

class BookController extends AbstractActionController
{
    private $bookModel;
    private $logModel;
    private $userModel;
    private $authService;

    public function __construct(
        BookModel $bookModel,
        LogModel $logModel,
        AuthenticationService $authService,
        UserModel $userModel
    ) {
        $this->bookModel = $bookModel;
        $this->logModel = $logModel;
        $this->authService = $authService;
        $this->userModel = $userModel;
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

        $books = $this->bookModel->getAllBooks();
        return new ViewModel([
            'books' => $books,
        ]);
    }

    public function addAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $title = $data['title'] ?? '';
            $author = $data['author'] ?? '';
            $quantity = (int) ($data['quantity'] ?? 0);

            if (empty($title) || empty($author) || $quantity < 0) {
                $this->flashMessenger()->addMessage('All fields are required, and quantity must be non-negative.');
                return $this->redirect()->toRoute('books');
            }

            try {
                $this->bookModel->addBook([
                    'title' => $title,
                    'author' => $author,
                    'quantity' => $quantity,
                ]);

                $identity = $this->getIdentity();
                $this->logModel->addLog(
                    'Book Added',
                    "Book '$title' added.",
                    $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                    $identity['type']
                );

                $this->flashMessenger()->addMessage('Book added successfully.');
            } catch (\Exception $e) {
                $this->flashMessenger()->addMessage('Error adding book: ' . $e->getMessage());
            }

            return $this->redirect()->toRoute('books');
        }

        return new ViewModel();
    }

    public function editAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Book ID not provided.');
            return $this->redirect()->toRoute('books');
        }

        $book = $this->bookModel->getBookById($id);
        if (!$book) {
            $this->flashMessenger()->addMessage('Book not found.');
            return $this->redirect()->toRoute('books');
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $title = $data['title'] ?? '';
            $author = $data['author'] ?? '';
            $quantity = (int) ($data['quantity'] ?? 0);

            if (empty($title) || empty($author) || $quantity < 0) {
                $this->flashMessenger()->addMessage('All fields are required, and quantity must be non-negative.');
                return $this->redirect()->toRoute('books');
            }

            try {
                $this->bookModel->updateBook($id, [
                    'title' => $title,
                    'author' => $author,
                    'quantity' => $quantity,
                ]);

                $identity = $this->getIdentity();
                $this->logModel->addLog(
                    'Book Updated',
                    "Book ID $id updated.",
                    $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                    $identity['type']
                );

                $this->flashMessenger()->addMessage('Book updated successfully.');
            } catch (\Exception $e) {
                $this->flashMessenger()->addMessage('Error updating book: ' . $e->getMessage());
            }

            return $this->redirect()->toRoute('books');
        }

        return new ViewModel([
            'book' => $book,
        ]);
    }

    public function deleteAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Book ID not provided.');
            return $this->redirect()->toRoute('books');
        }

        $book = $this->bookModel->getBookById($id);
        if (!$book) {
            $this->flashMessenger()->addMessage('Book not found.');
            return $this->redirect()->toRoute('books');
        }

        try {
            $this->bookModel->deleteBook($id);

            $identity = $this->getIdentity();
            $this->logModel->addLog(
                'Book Deleted',
                "Book ID $id deleted.",
                $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                $identity['type']
            );

            $this->flashMessenger()->addMessage('Book deleted successfully.');
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Error deleting book: ' . $e->getMessage());
        }

        return $this->redirect()->toRoute('books');
    }
}