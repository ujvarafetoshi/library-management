<?php

declare(strict_types=1);

namespace Application\Model;

class Payment
{
    public $id;
    public $borrow_id;
    public $student_id;
    public $amount;
    public $payment_date;

    public function exchangeArray(array $data): void
    {
        $this->id = !empty($data['id']) ? (int)$data['id'] : null;
        $this->borrow_id = !empty($data['borrow_id']) ? (int)$data['borrow_id'] : null;
        $this->student_id = !empty($data['student_id']) ? (int)$data['student_id'] : null;
        $this->amount = !empty($data['amount']) ? (float)$data['amount'] : null;
        $this->payment_date = !empty($data['payment_date']) ? $data['payment_date'] : null;
    }
}