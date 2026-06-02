<?php declare(strict_types=1);
namespace Modules\Dashboard\Services\Contracts;

interface DashboardServiceInterface
{
    public function getStats(): array;
}