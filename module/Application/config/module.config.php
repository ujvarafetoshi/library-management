<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter\DbTable;
use Laminas\Session\Container;

return [
    'Application',
    'Laminas\Router',
    'Laminas\Validator',

    'router' => [
        'routes' => [
            'borrows' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/borrows[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\BorrowController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'books' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/books[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\BookController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'students' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/students',
                    'defaults' => [
                        'controller' => Controller\StudentController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'users' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/users[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'login' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login',
                    'defaults' => [
                        'controller' => Controller\AuthenticationController::class,
                        'action' => 'login',
                    ],
                ],
            ],
            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => Controller\AuthenticationController::class,
                        'action' => 'logout',
                    ],
                ],
            ],
            'adminDashboard' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/admin-dashboard[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'adminDashboard',
                    ],
                ],
            ],
            'studentDashboard' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/student-dashboard[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'studentDashboard',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\BorrowController::class => function($container) {
                return new Controller\BorrowController(
                    $container->get(Model\BorrowModel::class),
                    $container->get(Model\StudentModel::class),
                    $container->get(Model\BookModel::class),
                    $container->get(Model\SettingsModel::class),
                    $container->get(Model\LogModel::class),
                    $container->get(Model\PaymentModel::class),
                    $container->get(Model\UserModel::class),
                    $container->get('Laminas\Authentication\AuthenticationService')
                );
            },
            Controller\BookController::class => function($container) {
                return new Controller\BookController(
                    $container->get(Model\BookModel::class),
                    $container->get(Model\LogModel::class),
                    $container->get('Laminas\Authentication\AuthenticationService'),
                    $container->get(Model\UserModel::class)
                );
            },
            Controller\UserController::class => function($container) {
                return new Controller\UserController(
                    $container->get(Model\UserModel::class),
                    $container->get(Model\StudentModel::class),
                    $container->get(Model\BorrowModel::class),
                    $container->get(Model\SettingsModel::class),
                    $container->get(Model\LogModel::class),
                    $container->get('Laminas\Authentication\AuthenticationService')
                );
            },
            Controller\AuthenticationController::class => function($container) {
                return new Controller\AuthenticationController(
                    $container->get('Laminas\Authentication\AuthenticationService'),
                    $container->get('systemAuthAdapter'),
                    $container->get('studentAuthAdapter'),
                    $container->get(Model\UserModel::class),
                    $container->get(Model\StudentModel::class)
                );
            },
            Controller\IndexController::class => function($container) {
                return new Controller\IndexController(
                    $container->get(Model\BorrowModel::class),
                    $container->get(Model\BookModel::class),
                    $container->get(Model\StudentModel::class),
                    $container->get(Model\SettingsModel::class),
                    $container->get(Model\LogModel::class),
                    $container->get('Laminas\Authentication\AuthenticationService'),
                    $container->get(Model\UserModel::class)
                );
            },
            Model\StudentModel::class => function($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new Model\Student());
                $tableGateway = new \Laminas\Db\TableGateway\TableGateway('students', $dbAdapter, null, $resultSetPrototype);
                return new Model\StudentModel($tableGateway);
            },
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
            'application/authentication/login' => __DIR__ . '/../view/application/authentication/login.phtml',
            'application/index/admin-dashboard' => __DIR__ . '/../view/application/index/admin-dashboard.phtml',
            'application/index/student-dashboard' => __DIR__ . '/../view/application/index/student-dashboard.phtml',
            'application/index/settings' => __DIR__ . '/../view/application/index/settings.phtml',
            'application/index/logs' => __DIR__ . '/../view/application/index/logs.phtml',
            'application/book/index' => __DIR__ . '/../view/application/book/index.phtml',
            'application/book/add' => __DIR__ . '/../view/application/book/add.phtml',
            'application/book/edit' => __DIR__ . '/../view/application/book/edit.phtml',
            'application/user/index' => __DIR__ . '/../view/application/user/index.phtml',
            'application/user/add-student' => __DIR__ . '/../view/application/user/add-student.phtml',
            'application/user/add-operator' => __DIR__ . '/../view/application/user/add-operator.phtml',
            'application/user/view-student' => __DIR__ . '/../view/application/user/view-student.phtml',
            'application/borrow/index' => __DIR__ . '/../view/application/borrow/index.phtml',
            'application/borrow/payments' => __DIR__ . '/../view/application/borrow/payments.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'service_manager' => [
        'aliases' => [
            'Laminas\Authentication\AuthenticationService' => 'ApplicationAuthService',
        ],
        'factories' => [
            Model\BookModel::class => function($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new Model\Book());
                $tableGateway = new \Laminas\Db\TableGateway\TableGateway('books', $dbAdapter, null, $resultSetPrototype);
                return new Model\BookModel($tableGateway);
            },
            Model\StudentModel::class => function($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new Model\Student());
                $tableGateway = new \Laminas\Db\TableGateway\TableGateway('students', $dbAdapter, null, $resultSetPrototype);
                return new Model\StudentModel($tableGateway);
            },
            Model\SettingsModel::class => function($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new Model\Setting());
                $tableGateway = new \Laminas\Db\TableGateway\TableGateway('settings', $dbAdapter, null, $resultSetPrototype);
                return new Model\SettingsModel($tableGateway);
            },
            Model\BorrowModel::class => function($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new Model\Borrow());
                $tableGateway = new \Laminas\Db\TableGateway\TableGateway('borrows', $dbAdapter, null, $resultSetPrototype);
                return new Model\BorrowModel($tableGateway, $container->get(Model\SettingsModel::class));
            },
            Model\UserModel::class => function($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new Model\User());
                $tableGateway = new \Laminas\Db\TableGateway\TableGateway('users', $dbAdapter, null, $resultSetPrototype);
                return new Model\UserModel($tableGateway);
            },
            Model\PaymentModel::class => function($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new Model\Payment());
                $tableGateway = new \Laminas\Db\TableGateway\TableGateway('payments', $dbAdapter, null, $resultSetPrototype);
                return new Model\PaymentModel($tableGateway);
            },
            Model\LogModel::class => function($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new Model\Log());
                $tableGateway = new \Laminas\Db\TableGateway\TableGateway('logs', $dbAdapter, null, $resultSetPrototype);
                return new Model\LogModel($tableGateway);
            },
            'systemAuthAdapter' => function ($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                return new DbTable($dbAdapter, 'users', 'email', 'password');
            },
            'studentAuthAdapter' => function ($container) {
                $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
                return new DbTable($dbAdapter, 'students', 'email', 'password');
            },
            'ApplicationAuthService' => function($container) {
                $authService = new AuthenticationService();
                $authService->setStorage(new \Laminas\Authentication\Storage\Session('AuthNamespace'));
                return $authService;
            },
        ],
    ],
    'session_config' => [
        'cookie_lifetime' => 3600, // 1 hour
        'gc_maxlifetime'  => 3600, // 1 hour
        'cookie_httponly' => true,
        'cookie_secure'   => false, // Set to true if using HTTPS
    ],
    'session_manager' => [
        'enable_default_container_manager' => true,
    ],
];