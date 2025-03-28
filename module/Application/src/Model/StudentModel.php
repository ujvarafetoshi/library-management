<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\TableGateway\TableGateway;

class StudentModel
{
    private $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getAllStudents(): array
    {
        $resultSet = $this->tableGateway->select();
        return iterator_to_array($resultSet);
    }

    public function getStudentById(int $id): ?Student
    {
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row = $rowset->current();
        return $row ?: null;
    }

    public function getStudentByEmail(string $email): ?Student
    {
        $rowset = $this->tableGateway->select(['email' => $email]);
        $row = $rowset->current();
        return $row ?: null;
    }

    public function addStudent(array $data): void
    {
        $this->tableGateway->insert($data);
    }

    public function updateStudent(int $id, array $data): void
    {
        $this->tableGateway->update($data, ['id' => $id]);
    }

    public function deleteStudent(int $id): void
    {
        $this->tableGateway->delete(['id' => $id]);
    }
}