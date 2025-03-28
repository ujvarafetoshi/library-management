<?php

namespace Application\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class LoginForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('login');

        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'email',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Email',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'password',
            'type' => Element\Password::class,
            'options' => [
                'label' => 'Password',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Login',
                'class' => 'btn btn-primary',
            ],
        ]);
    }
}