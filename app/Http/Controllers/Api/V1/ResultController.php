<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResultRequest;
use App\Http\Requests\Admin\UpdateResultStatusRequest;
use App\Http\Resources\Api\V1\ResultResource;
use App\Models\Event;
use App\Models\MatchGame;
use App\Models\Result;
use App\Support\ApiResponse;
use App\Support\ResultWorkflow;
use Illuminate\Http\JsonResponse;

class ResultController extends Controller
{
    public function store(StoreResultRequest $request, Event $event, MatchGame $matchGame): JsonResponse
    {
        abort_unless($matchGame->event()?->id === $event->id, 404);

        $result = app(ResultWorkflow::class)->record($matchGame, $request->validated(), $request->user());

        return ApiResponse::success(new ResultResource($result), 'Result recorded.', 201);
    }

    public function updateStatus(UpdateResultStatusRequest $request, Result $result): JsonResponse
    {
        $result = app(ResultWorkflow::class)->advanceStatus(
            $result,
            $request->validated('status'),
            $request->user(),
        );

        return ApiResponse::success(new ResultResource($result), 'Result status updated.');
    }
}