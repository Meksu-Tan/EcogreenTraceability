<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Api\Concerns\FormatsLegacyResponses;
use App\Http\Controllers\Controller;
use App\Services\Transaction\WipService;
use Illuminate\Http\Request;

class WipController extends Controller
{
    use FormatsLegacyResponses;

    protected WipService $wipService;

    public function __construct(WipService $wipService)
    {
        $this->wipService = $wipService;
    }

    public function balance(Request $request)
    {
        $request->merge(['mode' => $request->input('mode', 'LATEST')]);
        $rundownId = $request->input('rundownId', $request->input('rundown_id'));

        return response()->json(
            $this->legacyList($this->wipService->getBalance($request, $rundownId))
        );
    }

    public function feed(Request $request)
    {
        $request->merge(['mode' => $request->input('mode', 'LATEST')]);
        $feedId = $request->input('feedID', $request->input('feed_id'));

        return response()->json(
            $this->legacyList($this->wipService->getFeed($request, $feedId))
        );
    }

    public function rundown(Request $request)
    {
        $request->merge(['mode' => $request->input('mode', 'LATEST')]);
        $rundownId = $request->input('rundownId', $request->input('rundown_id'));

        return response()->json(
            $this->legacyList($this->wipService->getRundown($request, $rundownId))
        );
    }

    public function options(Request $request, string $option)
    {
        $data = $this->wipService->getOptions($request, $option);

        if (in_array($option, ['feed-tanks', 'rundown-tanks', 'specific-feed-tanks'], true)) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function storeFeed(Request $request)
    {
        $request->merge([
            'flag' => 'post_materialFeed',
            'mode' => $request->input('mode', 'ADD'),
        ]);
        $user = auth()->user()->name ?? 'System';
        $feature = $request->input('feature', 'FEED');

        return $this->legacyWrite(
            fn () => $this->wipService->storeFeed($user, $request),
            $feature,
            $request->input('mode', 'ADD')
        );
    }

    public function storeRundown(Request $request)
    {
        $request->merge([
            'flag' => 'post_materialRundown',
            'mode' => $request->input('mode', 'ADD'),
        ]);
        $user = auth()->user()->name ?? 'System';
        $feature = $request->input('feature', 'RUNDOWN');

        return $this->legacyWrite(
            fn () => $this->wipService->storeRundown($user, $request),
            $feature,
            $request->input('mode', 'ADD')
        );
    }

    public function cancelFeed(Request $request)
    {
        $request->merge([
            'flag' => 'post_cancelFeed',
            'mode' => $request->input('mode', 'delete'),
        ]);
        $user = auth()->user()->name ?? 'System';
        $traceNo = $request->input('traceNo', '');

        return $this->legacyWrite(
            fn () => $this->wipService->cancelFeed($user, $request),
            'FEED ' . $traceNo,
            'delete'
        );
    }

    public function cancelRundown(Request $request)
    {
        $request->merge([
            'flag' => 'post_cancelRundown',
            'mode' => $request->input('mode', 'delete'),
        ]);
        $user = auth()->user()->name ?? 'System';
        $traceNo = $request->input('traceNo', '');

        return $this->legacyWrite(
            fn () => $this->wipService->cancelRundown($user, $request),
            'RUNDOWN ' . $traceNo,
            'delete'
        );
    }
}
