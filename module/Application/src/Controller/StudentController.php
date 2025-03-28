<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Model\StudentModel;

class StudentController extends AbstractActionController
{
    private $studentModel;

    public function __construct(StudentModel $studentModel)
    {
        $this->studentModel = $studentModel;
    }

    public function indexAction()
    {
        return new ViewModel([
            'students' => $this->studentModel->fetchAll(),
        ]);
    }

    /*{
        $students = $this->studentModel->fetchAll();
        return new ViewModel(['students' => $students]);
    }
    */
}
