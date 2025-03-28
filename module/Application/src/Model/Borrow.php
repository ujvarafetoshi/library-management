<?php

declare(strict_types=1);

namespace Application\Model;

class Borrow
{
    public $id;
    public $student_id;
    public $book_id;
    public $borrow_date;
    public $due_date;
    public $return_date;
    public $status;
    public $borrow_fee;
    public $student_email; // Add this property
    public $book_title;   // Add this property

    public function exchangeArray(array $data): void
    {
        $this->id = !empty($data['id']) ? (int)$data['id'] : null;
        $this->student_id = !empty($data['student_id']) ? (int)$data['student_id'] : null;
        $this->book_id = !empty($data['book_id']) ? (int)$data['book_id'] : null;
        $this->borrow_date = !empty($data['borrow_date']) ? $data['borrow_date'] : null;
        $this->due_date = !empty($data['due_date']) ? $data['due_date'] : null;
        $this->return_date = !empty($data['return_date']) ? $data['return_date'] : null;
        $this->status = !empty($data['status']) ? $data['status'] : null;
        $this->borrow_fee = !empty($data['borrow_fee']) ? (float)$data['borrow_fee'] : 0.00;
        $this->student_email = !empty($data['student_email']) ? $data['student_email'] : null; // Map the computed field
        $this->book_title = !empty($data['book_title']) ? $data['book_title'] : null;         // Map the computed field
    }

    public function getArrayCopy(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'book_id' => $this->book_id,
            'borrow_date' => $this->borrow_date,
            'due_date' => $this->due_date,
            'return_date' => $this->return_date,
            'status' => $this->status,
            'borrow_fee' => $this->borrow_fee,
            'student_email' => $this->student_email,
            'book_title' => $this->book_title,
        ];
    }

    public function getPenalty(float $penaltyRate): float
    {
        if ($this->status !== 'Aktiv') {
            return 0.00;
        }

        $dueDate = new \DateTime($this->due_date);
        $currentDate = new \DateTime();
        if ($currentDate <= $dueDate) {
            return 0.00;
        }

        $daysOverdue = $currentDate->diff($dueDate)->days;
        return $daysOverdue * $penaltyRate;
    }
}