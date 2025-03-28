<?php

declare(strict_types=1);

namespace Application\Model;

class Book
{
    public $id;
    public $title;
    public $author;
    public $quantity;
    public $status;

    public function exchangeArray(array $data): void
    {
        $this->id = !empty($data['id']) ? (int)$data['id'] : null;
        $this->title = !empty($data['title']) ? $data['title'] : null;
        $this->author = !empty($data['author']) ? $data['author'] : null;
        $this->quantity = !empty($data['quantity']) ? (int)$data['quantity'] : null;
        $this->status = !empty($data['status']) ? $data['status'] : null;
    }

    public function getArrayCopy(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'quantity' => $this->quantity,
            'status' => $this->status,
        ];
    }
}