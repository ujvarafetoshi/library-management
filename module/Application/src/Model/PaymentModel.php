<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;

class PaymentModel
{
    private $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function addPayment(array $data): void
    {
        $this->tableGateway->insert($data);
    }

    public function getPaymentsByBorrowId(int $borrowId): array
    {
        $select = new Select($this->tableGateway->getTable());
        $select->where(['borrow_id' => $borrowId]);
        $resultSet = $this->tableGateway->selectWith($select);
        return iterator_to_array($resultSet);
    }

    public function getAllPayments(): array
    {
        $select = new Select($this->tableGateway->getTable());
        $select->join('students', 'payments.student_id = students.id', ['student_email' => 'email'], 'LEFT')
               ->join('borrows', 'payments.borrow_id = borrows.id', [], 'LEFT')
               ->join('books', 'borrows.book_id = books.id', ['book_title' => 'title'], 'LEFT');
        $resultSet = $this->tableGateway->selectWith($select);
        return iterator_to_array($resultSet);
    }
}