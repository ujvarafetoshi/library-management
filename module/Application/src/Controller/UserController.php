<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Model\UserModel;
use Application\Model\StudentModel;
use Application\Model\BorrowModel;
use Application\Model\SettingsModel;
use Application\Model\LogModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\Session\Container;

class UserController extends AbstractActionController
{
    private $userModel;
    private $studentModel;
    private $borrowModel;
    private $settingsModel;
    private $logModel;
    private $authService;

    public function __construct(
        UserModel $userModel,
        StudentModel $studentModel,
        BorrowModel $borrowModel,
        SettingsModel $settingsModel,
        LogModel $logModel,
        AuthenticationService $authService
    ) {
        $this->userModel = $userModel;
        $this->studentModel = $studentModel;
        $this->borrowModel = $borrowModel;
        $this->settingsModel = $settingsModel;
        $this->logModel = $logModel;
        $this->authService = $authService;
    }

    private function checkAccess($requireSuperAdmin = false): bool
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

        if ($requireSuperAdmin && $identity['role'] !== 'Super Admin') {
            $this->flashMessenger()->addMessage('Access denied. Super Admins only.');
            $this->redirect()->toRoute('adminDashboard');
            return false;
        }

        return true;
    }

    public function indexAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        $users = $this->userModel->getAllUsers();
        $students = $this->studentModel->getAllStudents();

        return new ViewModel([
            'users' => $users,
            'students' => $students,
        ]);
    }

    private function getIdentity()
    {
        $sessionContainer = new Container('AuthNamespace');
        return $this->authService->getIdentity() ?? $sessionContainer->identity;
    }

    public function addStudentAction()
    {
        if (!$this->checkAccess(true)) {
            return $this->redirect()->toRoute('login');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (!empty($data['email']) && !empty($data['password']) && !empty($data['first_name']) && !empty($data['last_name'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $this->studentModel->addStudent($data);

                $identity = $this->getIdentity();
                $this->logModel->addLog(
                    'Student Added',
                    "Student {$data['email']} added.",
                    $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                    $identity['type']
                );

                $this->flashMessenger()->addMessage('Student added successfully.');
                return $this->redirect()->toRoute('users');
            } else {
                $this->flashMessenger()->addMessage('All fields are required.');
            }
        }

        return new ViewModel();
    }

    public function addOperatorAction()
    {
        if (!$this->checkAccess(true)) {
            return $this->redirect()->toRoute('login');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (!empty($data['email']) && !empty($data['password']) && !empty($data['name'])) {
                $data['role'] = $data['role'] ?? 'Operator';
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $this->userModel->addUser($data);

                $identity = $this->getIdentity();
                $this->logModel->addLog(
                    'Operator Added',
                    "Operator {$data['email']} added.",
                    $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                    $identity['type']
                );

                $this->flashMessenger()->addMessage('Operator added successfully.');
                return $this->redirect()->toRoute('users');
            } else {
                $this->flashMessenger()->addMessage('All fields are required.');
            }
        }

        return new ViewModel();
    }

    public function deleteStudentAction()
    {
        if (!$this->checkAccess(true)) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Student ID not provided.');
            return $this->redirect()->toRoute('users');
        }

        try {
            $this->studentModel->deleteStudent($id);

            $identity = $this->getIdentity();
            $this->logModel->addLog(
                'Student Deleted',
                "Student ID {$id} deleted.",
                $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                $identity['type']
            );

            $this->flashMessenger()->addMessage('Student deleted successfully.');
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Error deleting student: ' . $e->getMessage());
        }

        return $this->redirect()->toRoute('users');
    }

    public function deleteOperatorAction()
    {
        if (!$this->checkAccess(true)) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Operator ID not provided.');
            return $this->redirect()->toRoute('users');
        }

        try {
            $this->userModel->deleteUser($id);

            $identity = $this->getIdentity();
            $this->logModel->addLog(
                'Operator Deleted',
                "Operator ID {$id} deleted.",
                $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                $identity['type']
            );

            $this->flashMessenger()->addMessage('Operator deleted successfully.');
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Error deleting operator: ' . $e->getMessage());
        }

        return $this->redirect()->toRoute('users');
    }

    public function editStudentAction()
    {
        if (!$this->checkAccess(true)) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Student ID not provided.');
            return $this->redirect()->toRoute('users');
        }

        try {
            $student = $this->studentModel->getStudentById($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Student not found.');
            return $this->redirect()->toRoute('users');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (!empty($data['email']) && !empty($data['first_name']) && !empty($data['last_name'])) {
                $student->email = $data['email'];
                $student->first_name = $data['first_name'];
                $student->last_name = $data['last_name'];
                if (!empty($data['password'])) {
                    $student->password = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                $this->studentModel->updateStudent($id, [
                    'email' => $student->email,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'password' => $student->password ?? $student->password
                ]);

                $identity = $this->getIdentity();
                $this->logModel->addLog(
                    'Student Updated',
                    "Student ID {$id} updated.",
                    $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                    $identity['type']
                );

                $this->flashMessenger()->addMessage('Student updated successfully.');
                return $this->redirect()->toRoute('users');
            } else {
                $this->flashMessenger()->addMessage('Email, first name, and last name are required.');
            }
        }

        $borrowingHistory = $this->borrowModel->getBorrowingHistoryByStudentEmail($student->email);

        return new ViewModel([
            'student' => $student,
            'borrowingHistory' => $borrowingHistory,
            'settingsModel' => $this->settingsModel,
        ]);
    }

    public function viewStudentAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Student ID not provided.');
            return $this->redirect()->toRoute('users');
        }

        try {
            $student = $this->studentModel->getStudentById($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Student not found.');
            return $this->redirect()->toRoute('users');
        }

        $borrowingHistory = $this->borrowModel->getBorrowingHistoryByStudentEmail($student->email);

        return new ViewModel([
            'student' => $student,
            'borrowingHistory' => $borrowingHistory,
            'settingsModel' => $this->settingsModel,
        ]);
    }

    // Add editOperatorAction for editing operator details
    public function editOperatorAction()
    {
        if (!$this->checkAccess(true)) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Operator ID not provided.');
            return $this->redirect()->toRoute('users');
        }

        try {
            $operator = $this->userModel->getUserById($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Operator not found.');
            return $this->redirect()->toRoute('users');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (!empty($data['email']) && !empty($data['name']) && !empty($data['role'])) {
                $operator->email = $data['email'];
                $operator->name = $data['name'];
                $operator->role = $data['role'];
                if (!empty($data['password'])) {
                    $operator->password = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                $this->userModel->updateUser($id, [
                    'email' => $operator->email,
                    'name' => $operator->name,
                    'role' => $operator->role,
                    'password' => $operator->password ?? $operator->password
                ]);

                $identity = $this->getIdentity();
                $this->logModel->addLog(
                    'Operator Updated',
                    "Operator ID {$id} updated.",
                    $identity['type'] === 'System User' ? $this->userModel->getUserByEmail($identity['email'])->id : null,
                    $identity['type']
                );

                $this->flashMessenger()->addMessage('Operator updated successfully.');
                return $this->redirect()->toRoute('users');
            } else {
                $this->flashMessenger()->addMessage('Email, name, and role are required.');
            }
        }

        return new ViewModel([
            'operator' => $operator,
        ]);
    }

    // Add viewOperatorAction for viewing operator details
    public function viewOperatorAction()
    {
        if (!$this->checkAccess()) {
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            $this->flashMessenger()->addMessage('Operator ID not provided.');
            return $this->redirect()->toRoute('users');
        }

        try {
            $operator = $this->userModel->getUserById($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage('Operator not found.');
            return $this->redirect()->toRoute('users');
        }

        return new ViewModel([
            'operator' => $operator,
        ]);
    }
}