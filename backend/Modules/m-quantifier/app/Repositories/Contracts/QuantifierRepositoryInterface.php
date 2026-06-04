<?php declare(strict_types=1);

namespace Modules\Quantifier\Repositories\Contracts;

interface QuantifierRepositoryInterface
{
    public function getQuantifierList(array $filters = []): array;
    public function getActiveFlowmeters(): array;
    public function getQuantifierDetail(int $id): ?array;
    public function createQuantifier(string $resetDate, string $flowmeter, float $value, string $remark, string $user): int;
    public function updateQuantifier(int $id, string $resetDate, string $flowmeter, float $value, string $remark, string $user): array;
    public function deactivateQuantifier(int $id, string $user): array;
    public function activateQuantifier(int $id, string $user): array;
}
