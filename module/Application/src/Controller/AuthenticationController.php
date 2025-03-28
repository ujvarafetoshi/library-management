<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Form\LoginForm;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter\DbTable;
use Application\Model\UserModel;
use Application\Model\StudentModel;
use Laminas\Session\Container; // Add this for manual session handling

class AuthenticationController extends AbstractActionController
{
    private $authService;
    private $systemAuthAdapter;
    private $studentAuthAdapter;
    private $userModel;
    private $studentModel;

    public function __construct(
        AuthenticationService $authService,
        DbTable $systemAuthAdapter,
        DbTable $studentAuthAdapter,
        UserModel $userModel,
        StudentModel $studentModel
    ) {
        $this->authService = $authService;
        $this->systemAuthAdapter = $systemAuthAdapter;
        $this->studentAuthAdapter = $studentAuthAdapter;
        $this->userModel = $userModel;
        $this->studentModel = $studentModel;
    }

    public function loginAction()
    {
        $form = new LoginForm();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();
                $email = $data['email'];
                $password = $data['password'];

                // Debug: Log the form data
                error_log('Form Data: ' . print_r($data, true));

                // First, try authenticating as a student
                $this->studentAuthAdapter->setIdentity($email);
                $this->studentAuthAdapter->setCredential($password);
                $result = $this->authService->authenticate($this->studentAuthAdapter);

                $identity = null;
                if ($result->isValid()) {
                    // Student authentication successful
                    $identity = [
                        'email' => $email,
                        'type' => 'Student',
                        'role' => 'Student',
                    ];
                    error_log('Authenticated as Student: ' . $email);
                } else {
                    // If student authentication fails, try system user
                    $this->systemAuthAdapter->setIdentity($email);
                    $this->systemAuthAdapter->setCredential($password);
                    $result = $this->authService->authenticate($this->systemAuthAdapter);

                    if ($result->isValid()) {
                        // System user authentication successful
                        $user = $this->userModel->getUserByEmail($email);
                        $identity = [
                            'email' => $email,
                            'type' => 'System User',
                            'role' => $user ? $user->role : 'Operator',
                        ];
                        error_log('Authenticated as System User: ' . $email . ', Role: ' . $identity['role']);
                    }
                }

                if ($identity) {
                    $storage = $this->authService->getStorage();
                    $storage->write($identity);
                    // Debug: Log the stored identity
                    error_log('Stored Identity: ' . print_r($identity, true));

                    // Debug: Verify identity immediately after storing
                    $retrievedIdentity = $this->authService->getIdentity();
                    error_log('Retrieved Identity After Storage: ' . print_r($retrievedIdentity, true));

                    // Manually store the identity in a session container as a fallback
                    $sessionContainer = new Container('AuthNamespace');
                    $sessionContainer->identity = $identity;
                    error_log('Manually Stored Identity in Session: ' . print_r($sessionContainer->identity, true));

                    // Redirect based on type
                    if ($identity['type'] === 'Student') {
                        error_log('Redirecting to studentDashboard');
                        return $this->redirect()->toRoute('studentDashboard');
                    } else {
                        error_log('Redirecting to adminDashboard');
                        return $this->redirect()->toRoute('adminDashboard');
                    }
                } else {
                    // Debug: Log authentication failure
                    error_log('Authentication Failed: ' . print_r($result->getMessages(), true));
                    $this->flashMessenger()->addMessage('Invalid credentials. Please try again.');
                    return $this->redirect()->toRoute('login');
                }
            } else {
                // Debug: Log form validation errors
                error_log('Form Validation Errors: ' . print_r($form->getMessages(), true));
            }
        }

        return new ViewModel([
            'form' => $form,
        ]);
    }

    public function logoutAction()
    {
        $this->authService->clearIdentity();
        // Also clear the manual session container
        $sessionContainer = new Container('AuthNamespace');
        $sessionContainer->getManager()->getStorage()->clear('AuthNamespace');
        $this->flashMessenger()->addMessage('You have been logged out.');
        return $this->redirect()->toRoute('login');
    }
}