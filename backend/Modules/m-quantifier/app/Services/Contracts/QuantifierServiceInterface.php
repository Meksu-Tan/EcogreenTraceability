<?php
declare(strict_types=1);
namespace Modules\Quantifier\Services\Contracts;

interface QuantifierServiceInterface
{
    public function getQuantifierList(array $filters = []): array;
    public function getActiveFlowmeters(): array;
    public function getQuantifierDetail(int $id): ?array;
    public function storeQuantifier(string $user, array $data): array;
    public function deactivateQuantifier(string $user, int $id): array;
    public function activateQuantifier(string $user, int $id): array;
}
