<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;

class LogModel
{
    private $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function addLog(string $action, string $description, ?int $userId, string $userType): void
    {
        $this->tableGateway->insert([
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'user_type' => $userType,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getAllLogs(): array
    {
        $select = new Select($this->tableGateway->getTable());
        $select->join('users', 'logs.user_id = users.id', ['user_email' => 'email'], 'LEFT')
               ->order('created_at DESC');
        $resultSet = $this->tableGateway->selectWith($select);
        return iterator_to_array($resultSet);
    }
}