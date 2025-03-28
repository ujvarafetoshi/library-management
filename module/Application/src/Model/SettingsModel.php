<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\TableGateway\TableGateway;

class SettingsModel
{
    private $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getSetting(string $key): ?string
    {
        $rowset = $this->tableGateway->select(['setting_key' => $key]);
        $row = $rowset->current();
        return $row ? $row->value : null;
    }

    public function getAllSettings(): array
    {
        $resultSet = $this->tableGateway->select();
        return iterator_to_array($resultSet);
    }

    public function updateSetting(string $key, $value): void
    {
        $value = is_float($value) ? number_format($value, 2, '.', '') : (string) $value;

        $rowset = $this->tableGateway->select(['setting_key' => $key]);
        if ($rowset->current()) {
            $this->tableGateway->update(
                ['value' => $value],
                ['setting_key' => $key]
            );
        } else {
            $this->tableGateway->insert([
                'setting_key' => $key,
                'value' => $value,
            ]);
        }
    }
}