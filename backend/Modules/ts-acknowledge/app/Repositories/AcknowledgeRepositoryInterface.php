<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Repositories;

interface AcknowledgeRepositoryInterface
{
    public function getAcknowledgeData(string $plantCode, string $date = '', string $type = 'WIP'): array;

    public function getBalanceData(string $plantCode, string $prefix, int $page = 1, int $perPage = 15): array;

    public function countBalanceData(string $plantCode, string $prefix): int;

    public function saveAcknowledgeData(array $data): object;
}
