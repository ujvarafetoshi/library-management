<?php

declare(strict_types=1);

namespace Application\Model;

class Student
{
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $borrowing_history;
    public $penalties;

    public function exchangeArray(array $data): void
    {
        $this->id = !empty($data['id']) ? (int)$data['id'] : null;
        $this->first_name = !empty($data['first_name']) ? $data['first_name'] : null;
        $this->last_name = !empty($data['last_name']) ? $data['last_name'] : null;
        $this->email = !empty($data['email']) ? $data['email'] : null;
        $this->password = !empty($data['password']) ? $data['password'] : null;
        $this->borrowing_history = !empty($data['borrowing_history']) ? $data['borrowing_history'] : null;
        $this->penalties = !empty($data['penalties']) ? (float)$data['penalties'] : 0.00;
    }
}