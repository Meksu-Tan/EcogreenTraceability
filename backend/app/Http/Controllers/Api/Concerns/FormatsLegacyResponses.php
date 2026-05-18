<?php

namespace App\Http\Controllers\Api\Concerns;

trait FormatsLegacyResponses
{
    protected function legacyWrite(callable $callback, string $feature, string $mode)
    {
        try {
            $return = $callback();
            $response = $this->legacyStatus($return, $feature, $mode);

            return response()->json($response, $response['status'] ? 200 : 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 0,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function legacyStatus($return, string $feature, string $mode): array
    {
        $code = null;

        if (is_array($return)) {
            $first = $return[0] ?? null;
            $code = is_array($first) ? ($first['response'] ?? null) : ($first->response ?? null);
        } elseif (is_object($return)) {
            $code = $return->response ?? null;
        }

        $code = (string) ($code ?? '0');

        $message = match ($code) {
            '1' => "Success {$mode} {$feature}",
            '2' => "{$feature} already exists",
            '3' => "{$feature} Not Enough Reserve!",
            '4' => "{$feature} Feed N/A!",
            '5' => "{$feature} Feed Qty undefined!",
            '6' => "{$feature} No Supplier Traced!",
            '7' => "{$feature} Double Trace no!",
            '9' => 'Source or Destination Tank is inactive',
            '98' => 'Entry data not found',
            '99' => "{$feature} Period Locked!",
            default => "Failed {$mode} {$feature}",
        };

        return [
            'success' => $code === '1',
            'status' => $code === '1' ? 1 : 0,
            'response' => $code,
            'message' => $message,
            'data' => $return,
        ];
    }

    protected function legacyList($rows): array
    {
        if ($rows instanceof \Illuminate\Support\Collection) {
            $rows = $rows->all();
        }

        return [
            'success' => true,
            'data' => array_values(is_array($rows) ? $rows : []),
        ];
    }
}
