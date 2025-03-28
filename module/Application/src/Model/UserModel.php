<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;

class UserModel
{
    private $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getAllUsers(): array
    {
        $select = new Select($this->tableGateway->getTable());
        $resultSet = $this->tableGateway->selectWith($select);
        return iterator_to_array($resultSet);
    }

    public function getUserById(int $id): User
    {
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception('User not found');
        }
        return $row;
    }

    public function getUserByEmail(string $email): ?User
    {
        $rowset = $this->tableGateway->select(['email' => $email]);
        $row = $rowset->current();
        return $row ?: null;
    }

    public function addUser(array $data): void
    {
        $this->tableGateway->insert([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Password is already hashed in the controller
            'role' => $data['role'],
        ]);
    }

    public function updateUser(int $id, array $data): void
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];
        if (!empty($data['password'])) {
            $updateData['password'] = $data['password']; // Password is already hashed in the controller
        }
        $this->tableGateway->update($updateData, ['id' => $id]);
    }

    public function deleteUser(int $id): void
    {
        $this->tableGateway->delete(['id' => $id]);
    }
}