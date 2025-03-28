<?php

declare(strict_types=1);

namespace Application\Model;

class User
{
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;

    public function exchangeArray(array $data): void
    {
        $this->id = !empty($data['id']) ? (int)$data['id'] : null;
        $this->name = !empty($data['name']) ? $data['name'] : null;
        $this->email = !empty($data['email']) ? $data['email'] : null;
        $this->password = !empty($data['password']) ? $data['password'] : null;
        $this->role = !empty($data['role']) ? $data['role'] : null;
    }
}