<?php

namespace Modules\Inquiry\Repositories\Contracts;

interface InquiryRepositoryInterface
{
    public function getStockList(array $filters): array;
    public function getStockDetail(int $id): ?array;
    public function getTsReport(array $filters): array;
    public function getRmReport(array $filters): array;
}