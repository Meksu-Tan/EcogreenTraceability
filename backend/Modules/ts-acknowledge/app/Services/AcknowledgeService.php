<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Services;

use Modules\TsAcknowledge\Repositories\AcknowledgeRepositoryInterface;
use Modules\TsWip\Models\WipSection;

class AcknowledgeService
{
    public function __construct(private AcknowledgeRepositoryInterface $repository) {}

    public function save(array $data): object
    {
        return $this->repository->saveAcknowledgeData($data);
    }

    private function computeMatchStatus(?float $eoDls, ?float $dcs): string
    {
        if ($eoDls === null && $dcs === null) {
            return 'pending';
        }
        if ($eoDls === null || $dcs === null) {
            return 'pending';
        }

        return abs((float) $eoDls - (float) $dcs) < 0.001 ? 'match' : 'mismatch';
    }

    public function getDashboardStructure(string $plantCode, string $date, string $type = 'WIP', int $page = 1, int $perPage = 15, ?int $sectionId = null): array
    {
        if ($type === 'WIP') {
            return $this->getWipStructure($plantCode, $date, $page, $perPage, $sectionId);
        }

        $prefix = $type === 'TRANSFER' ? '7' : '8';

        return $this->getBalanceStructure($type, $plantCode, $prefix, $page, $perPage);
    }

    private function getWipStructure(string $plantCode, string $date, int $page, int $perPage, ?int $sectionId = null): array
    {
        $query = WipSection::with(['steps' => function ($query) {
            $query->whereIn('step_type', ['feed', 'rundown'])
                ->orderBy('sort_order', 'asc');
        }])
            ->where('status', 1);

        if ($sectionId !== null) {
            $query->where('id', $sectionId);
        }

        $allSections = $query->orderBy('sort_order', 'asc')->get();

        $ackData = collect($this->repository->getAcknowledgeData($plantCode, $date, 'WIP'));

        $allSteps = [];
        foreach ($allSections as $section) {
            foreach ($section->steps as $step) {
                $existing = $ackData->where('section_id', $section->id)
                    ->where('step_type', $step->step_type)
                    ->first();
                $allSteps[] = [
                    'sectionObj' => $section,
                    'stepData' => [
                        'id' => $existing['id'] ?? null,
                        'step_id' => $step->id,
                        'step_type' => $step->step_type,
                        'label' => explode(' (', $step->label)[0] ?? $step->label,
                        'material_id' => $step->step_type === 'feed' ? $step->feed_id : $step->rundown_id,
                        'tank' => $step->dcs_tag,
                        'trace_no' => $existing['trace_no'] ?? null,
                        'entry_date' => $existing['entry_date'] ?? null,
                        'eo_dls_qty' => $existing['eo_dls_qty'] ?? null,
                        'dcs_qty' => $existing['dcs_qty'] ?? null,
                        'manual_qty' => $existing['manual_qty'] ?? null,
                        'match_status' => $this->computeMatchStatus(
                            $existing['eo_dls_qty'] ?? null,
                            $existing['dcs_qty'] ?? null
                        ),
                        'created_by' => $existing['created_by'] ?? null,
                        'updated_at' => $existing['updated_at'] ?? null,
                    ],
                ];
            }
        }

        $totalSteps = count($allSteps);
        $lastPage = max(1, (int) ceil($totalSteps / $perPage));
        $offset = ($page - 1) * $perPage;
        $pageSteps = array_slice($allSteps, $offset, $perPage);

        $dashboardMap = [];
        foreach ($pageSteps as $item) {
            $section = $item['sectionObj'];
            $sid = $section->id;
            if (! isset($dashboardMap[$sid])) {
                $dashboardMap[$sid] = [
                    'section_id' => $sid,
                    'section_code' => $section->code,
                    'section_name' => $section->name,
                    'modes' => [[
                        'mode' => 'DEFAULT',
                        'label' => 'Default',
                        'steps' => [],
                    ]],
                ];
            }
            $dashboardMap[$sid]['modes'][0]['steps'][] = $item['stepData'];
        }

        return [
            'data' => array_values($dashboardMap),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalSteps,
                'last_page' => $lastPage,
            ],
            'allSections' => $allSections->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])->values()->toArray(),
        ];
    }

    private function getBalanceStructure(string $type, string $plantCode, string $prefix, int $page, int $perPage): array
    {
        $balanceData = $this->repository->getBalanceData($plantCode, $prefix, $page, $perPage);
        $total = $this->repository->countBalanceData($plantCode, $prefix);
        $ackData = collect($this->repository->getAcknowledgeData($plantCode, '', $type));

        $lastPage = max(1, (int) ceil($total / $perPage));

        $rows = [];
        foreach ($balanceData as $item) {
            $item = (array) $item;
            $existing = $ackData->where('transaction_id', $item['trace_no'])->first();

            $rows[] = [
                'id' => $existing['id'] ?? null,
                'transaction_id' => $item['trace_no'],
                'entry_date' => $item['entry_date'] ?? $date,
                'trace_no' => $item['trace_no'],
                'material_name' => $item['material_name'] ?? null,
                'in_qty' => $item['in_qty'] ?? null,
                'out_qty' => $item['out_qty'] ?? null,
                'sloc_id' => $item['sloc_id'] ?? null,
                'sloc_name' => $item['sloc_name'] ?? null,
                'eo_dls_qty' => $existing['eo_dls_qty'] ?? null,
                'dcs_qty' => $existing['dcs_qty'] ?? null,
                'manual_qty' => $existing['manual_qty'] ?? null,
                'match_status' => $this->computeMatchStatus(
                    $existing['eo_dls_qty'] ?? null,
                    $existing['dcs_qty'] ?? null
                ),
                'created_by' => $item['created_by'] ?? $existing['created_by'] ?? null,
                'updated_at' => $item['created_at'] ?? $existing['updated_at'] ?? null,
            ];
        }

        return [
            'data' => [
                [
                    'section_id' => null,
                    'section_code' => $type,
                    'section_name' => ucfirst(strtolower($type)).' History',
                    'modes' => [
                        [
                            'mode' => 'DEFAULT',
                            'steps' => $rows,
                        ],
                    ],
                ],
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }
}
