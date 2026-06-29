<?php
declare(strict_types=1);
namespace Modules\TsWip\Services;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\TraceHelper;

class WipTreeService
{
    public function getTree(?string $idPlant): array
    {
        return $this->buildTree($idPlant);
    }

    private function buildTree(?string $idPlant): array
    {
        $sections = DB::connection('eudr_ts')->table('m_wip_section')
            ->where('status', 1)
            ->where(function ($query) use ($idPlant): void {
                $query->whereNull('plant_id');
                if ($idPlant && $idPlant !== '0') {
                    $query->orWhere('plant_id', $idPlant);
                }
            })
            ->orderBy('sort_order')
            ->get();

        $sectionIds = $sections->pluck('id')->all();
        $steps = DB::connection('eudr_ts')->table('m_wip_process_step')
            ->whereIn('section_id', $sectionIds)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('section_id');

        $feedPrefixes = [];
        $rundownPrefixes = [];
        $modeConfigs = [];

        $treeSections = $sections->map(function ($section) use ($steps, &$modeConfigs, &$feedPrefixes, &$rundownPrefixes): array {
            $sectionSteps = ($steps[$section->id] ?? collect())
                ->map(function ($step) use (&$modeConfigs, &$feedPrefixes, &$rundownPrefixes): array {
                    $mapped = $this->mapStep($step);
                    if ($mapped['type'] === 'mode_switch') {
                        $modeConfigs[$mapped['modeGroup']] = $mapped['config'];
                    }
                    if ($mapped['type'] === 'feed' && $mapped['feedId']) {
                        $prefix = substr($mapped['feedId'], 0, 3);
                        $feedPrefixes[$prefix] = true;
                    }
                    if ($mapped['type'] === 'rundown' && $mapped['rundownId']) {
                        $prefix = substr($mapped['rundownId'], 0, 3);
                        $rundownPrefixes[$prefix] = true;
                    }
                    return $mapped;
                })
                ->values()
                ->all();

            return [
                'id' => (int) $section->id,
                'code' => (string) $section->code,
                'key' => 'section' . $section->code,
                'name' => (string) $section->name,
                'title' => (string) $section->name,
                'steps' => $sectionSteps,
            ];
        })->values()->all();

        $feedTraceMap = $this->fetchLatestTraces(array_keys($feedPrefixes), '3', $idPlant);
        $rundownTraceMap = $this->fetchLatestTraces(array_keys($rundownPrefixes), '2', $idPlant);

        foreach ($treeSections as &$section) {
            foreach ($section['steps'] as &$step) {
                if ($step['type'] === 'feed' && $step['feedId']) {
                    $prefix = substr($step['feedId'], 0, 3);
                    $step['latestTrace'] = $feedTraceMap[$prefix] ?? null;
                }
                if ($step['type'] === 'rundown' && $step['rundownId']) {
                    $prefix = substr($step['rundownId'], 0, 3);
                    $step['latestTrace'] = $rundownTraceMap[$prefix] ?? null;
                }
            }
        }

        return ['sections' => $treeSections, 'modeConfigs' => $modeConfigs];
    }

    private function fetchLatestTraces(array $prefixes, string $tracePrefix, ?string $idPlant): array
    {
        if (empty($prefixes)) return [];

        $placeholders = [];
        $bindings = [];

        foreach ($prefixes as $p) {
            $padded = str_pad((string) $p, 3, '0', STR_PAD_LEFT);
            $placeholders[] = '?';
            $bindings[] = $padded;
        }

        $where = implode(',', $placeholders);
        $plantFilter = ($idPlant && $idPlant !== '0') ? 'AND a.id_plant = ?' : '';

        if ($plantFilter) {
            $bindings[] = $idPlant;
        }

        $sql = "
            SELECT DISTINCT ON (wh)
                   SUBSTRING(CAST(a.to_trace_no AS TEXT), 8, 3) AS wh,
                   CAST(a.to_trace_no AS TEXT) AS trace_no,
                   a.entry_date,
                   a.curr_qtf
              FROM t_trace_header a
             WHERE SUBSTRING(CAST(a.to_trace_no AS TEXT), 1, 1) = ?
               AND CHAR_LENGTH(CAST(a.to_trace_no AS TEXT)) >= 14
               AND SUBSTRING(CAST(a.to_trace_no AS TEXT), 8, 3) IN ({$where})
               AND a.status = 1
               {$plantFilter}
             ORDER BY wh, a.id_trace_head DESC
        ";

        array_unshift($bindings, $tracePrefix);

        $rows = DB::connection('eudr_ts')->select($sql, $bindings);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->wh] = [
                'traceNo' => $row->trace_no,
                'entryDate' => $row->entry_date,
                'qty' => (float) ($row->curr_qtf ?? 0),
            ];
        }

        return $map;
    }

    private function mapStep(object $step): array
    {
        $config = $this->decodeJson($step->mode_options);
        $conditions = $this->decodeJson($step->conditions);
        $type = (string) $step->step_type;
        $entryId = $type === 'feed' ? $step->feed_id : $step->rundown_id;
        $title = (string) $step->label;

        return [
            'id' => $entryId ? (string) $entryId : (string) $step->id,
            'stepId' => (int) $step->id,
            'key' => $type . '-' . $step->id,
            'type' => $type,
            'label' => $title,
            'title' => $title,
            'button' => preg_replace('/S$/', '', $title),
            'feedId' => $step->feed_id ? (string) $step->feed_id : null,
            'rundownId' => $step->rundown_id ? (string) $step->rundown_id : null,
            'pipeNumber' => $step->pipe_number ? (string) $step->pipe_number : null,
            'tag' => $step->dcs_tag ? (string) $step->dcs_tag : null,
            'dcsTag' => $step->dcs_tag ? (string) $step->dcs_tag : null,
            'modeGroup' => $step->mode_group ? (string) $step->mode_group : null,
            'modeValue' => $step->mode_value ? (string) $step->mode_value : null,
            'conditions' => is_array($conditions) ? $conditions : [],
            'config' => is_array($config) ? $config : [],
            'icon' => str_starts_with($title, 'END') ? 'ri-flag-checkered-line' : 'ri-arrow-down-line',
            'latestTrace' => null,
        ];
    }

    private function decodeJson(mixed $value): ?array
    {
        if (!$value) return null;
        if (is_array($value)) return $value;
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : null;
    }
}
