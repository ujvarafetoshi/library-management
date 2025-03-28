<?php

declare(strict_types=1);

namespace Application\Model;

class Log
{
    public $id;
    public $action;
    public $description;
    public $user_id;
    public $user_type;
    public $created_at;

    public function exchangeArray(array $data): void
    {
        $this->id = !empty($data['id']) ? (int)$data['id'] : null;
        $this->action = !empty($data['action']) ? $data['action'] : null;
        $this->description = !empty($data['description']) ? $data['description'] : null;
        $this->user_id = !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $this->user_type = !empty($data['user_type']) ? $data['user_type'] : null;
        $this->created_at = !empty($data['created_at']) ? $data['created_at'] : null;
    }
}