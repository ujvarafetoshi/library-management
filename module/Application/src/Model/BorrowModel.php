<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Expression;

class BorrowModel
{
    private $tableGateway;
    private $settingsModel;

    public function __construct(TableGateway $tableGateway, SettingsModel $settingsModel)
    {
        $this->tableGateway = $tableGateway;
        $this->settingsModel = $settingsModel;
    }

    public function getAllBorrows(): array
    {
        $select = new Select($this->tableGateway->getTable());
        $select->columns([
            'id',
            'student_id',
            'book_id',
            'borrow_date',
            'due_date',
            'return_date',
            'status',
            'borrow_fee',
            'student_email' => new Expression('students.email'),
            'book_title' => new Expression('books.title'),
        ]);
        $select->join('students', 'borrows.student_id = students.id', [], $select::JOIN_LEFT);
        $select->join('books', 'borrows.book_id = books.id', [], $select::JOIN_LEFT);

        $resultSet = $this->tableGateway->selectWith($select);
        return iterator_to_array($resultSet);
    }

    public function getOverdueBorrows(): array
    {
        $select = new Select($this->tableGateway->getTable());
        $select->columns([
            'id',
            'student_id',
            'book_id',
            'borrow_date',
            'due_date',
            'return_date',
            'status',
            'borrow_fee',
            'student_email' => new Expression('students.email'),
            'book_title' => new Expression('books.title'),
        ]);
        $select->join('students', 'borrows.student_id = students.id', [], $select::JOIN_LEFT);
        $select->join('books', 'borrows.book_id = books.id', [], $select::JOIN_LEFT);
        $select->where([
            'borrows.status' => 'Aktiv',
            'borrows.due_date < ?' => date('Y-m-d'),
        ]);

        $resultSet = $this->tableGateway->selectWith($select);
        return iterator_to_array($resultSet);
    }

    public function getBorrowingHistoryByStudentEmail(string $email): array
    {
        $select = $this->tableGateway->getSql()->select();
        $select->join('students', 'borrows.student_id = students.id', [])
            ->join('books', 'borrows.book_id = books.id', ['book_title' => 'title'])
            ->where(['students.email' => $email]);
        $resultSet = $this->tableGateway->selectWith($select);
        return iterator_to_array($resultSet);
    }

    public function getBorrowById(int $id): Borrow
    {
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception('Borrow record not found');
        }
        return $row;
    }

    public function returnBook(int $borrowId): void
    {
        $this->tableGateway->update(
            ['status' => 'Returned', 'return_date' => date('Y-m-d')],
            ['id' => $borrowId]
        );
    }

    public function canStudentBorrow(int $studentId, int $borrowingLimit): bool
    {
        $select = new Select($this->tableGateway->getTable());
        $select->where(['student_id' => $studentId, 'status' => 'Aktiv']);
        $resultSet = $this->tableGateway->selectWith($select);
        $activeBorrows = $resultSet->count();

        return $activeBorrows < $borrowingLimit;
    }

    public function borrowBook(int $studentId, int $bookId, string $borrowDate, string $dueDate): void
    {
        $borrowingLimit = (int) $this->settingsModel->getSetting('borrowing_limit');
        if (!$this->canStudentBorrow($studentId, $borrowingLimit)) {
            throw new \Exception('Student has reached the borrowing limit.');
        }

        $borrowFee = (float) $this->settingsModel->getSetting('borrow_fee');

        $this->tableGateway->insert([
            'student_id' => $studentId,
            'book_id' => $bookId,
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate,
            'status' => 'Aktiv',
            'borrow_fee' => $borrowFee,
        ]);
    }

    public function countActiveBorrowsForBook(int $bookId): int
    {
        $select = new Select($this->tableGateway->getTable());
        $select->where(['book_id' => $bookId, 'status' => 'Aktiv']);
        $resultSet = $this->tableGateway->selectWith($select);
        return $resultSet->count();
    }
}