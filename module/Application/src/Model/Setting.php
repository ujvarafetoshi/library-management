<?php

declare(strict_types=1);

namespace Application\Model;

class Setting
{
    public $id;
    public $setting_key;
    public $value;

    public function exchangeArray(array $data): void
    {
        $this->setting_key = !empty($data['setting_key']) ? $data['setting_key'] : null; // Use setting_key
        $this->value = !empty($data['value']) ? $data['value'] : null;
    }

    public function getArrayCopy(): array
    {
        return [
            'id' => $this->id,
            'setting_key' => $this->setting_key,
            'value' => $this->value,
        ];
    }
}