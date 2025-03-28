<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class BookForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('book_form');

        $this->add([
            'name' => 'id',
            'type' => Element\Hidden::class,
        ]);

        $this->add([
            'name' => 'title',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Title',
            ],
            'attributes' => [
                'required' => true,
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'author',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Author',
            ],
            'attributes' => [
                'required' => true,
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'quantity',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Quantity',
            ],
            'attributes' => [
                'required' => true,
                'min' => 0,
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'status',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Status',
                'value_options' => [
                    'Available' => 'Available',
                    'Unavailable' => 'Unavailable',
                ],
            ],
            'attributes' => [
                'required' => true,
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Save',
                'class' => 'btn btn-primary',
            ],
        ]);
    }
}