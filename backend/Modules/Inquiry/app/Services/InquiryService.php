<?php

namespace Modules\Inquiry\Services;

use Modules\Inquiry\Repositories\Contracts\InquiryRepositoryInterface;

class InquiryService
{
    public function __construct(
        protected InquiryRepositoryInterface $inquiryRepository
    ) {}

    /**
     * Get stock list for inquiry page.
     */
    public function getStockList(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->inquiryRepository->getStockList($filters),
            'message' => 'Stock list retrieved successfully',
        ];
    }

    /**
     * Get stock detail by ID.
     */
    public function getStockDetail(int $id): array
    {
        $data = $this->inquiryRepository->getStockDetail($id);

        if ($data === null) {
            return [
                'status'  => 0,
                'data'    => null,
                'message' => 'Stock detail not found',
            ];
        }

        return [
            'status'  => 1,
            'data'    => $data,
            'message' => 'Stock detail retrieved successfully',
        ];
    }

    /**
     * Get Tank Storage (TS) Report.
     */
    public function getTsReport(array $filters = []): array
    {
        return [
            'status'  => 1,
            'data'    => $this->inquiryRepository->getTsReport($filters),
            'message' => 'TS Report retrieved successfully',
        ];
    }

    /**
     * Get Raw Material (RM) Report.
     */
    public function getRmReport(array $filters = []): array
    {
        return [
            'status'  => 1,
            'data'    => $this->inquiryRepository->getRmReport($filters),
            'message' => 'RM Report retrieved successfully',
        ];
    }
}