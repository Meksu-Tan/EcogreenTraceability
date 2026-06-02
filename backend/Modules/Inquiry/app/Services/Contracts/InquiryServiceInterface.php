<?php declare(strict_types=1);
namespace Modules\Inquiry\Services\Contracts;

interface InquiryServiceInterface
{
    public function getStockList(array $filters = []): array;
    public function getStockDetail(int $id): array;
    public function getTsReport(array $filters = []): array;
    public function getRmReport(array $filters = []): array;
}