<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResultAppealRequest;
use App\Http\Requests\Admin\UpdateResultAppealStatusRequest;
use App\Http\Resources\Api\V1\ResultAppealResource;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Support\ApiResponse;
use App\Support\AppealWorkflow;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ResultAppealController extends Controller
{
    public function store(StoreResultAppealRequest $request, Result $result): JsonResponse
    {
        try {
            $appeal = app(AppealWorkflow::class)->submit($result, $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            return ApiResponse::success(['error' => $exception->getMessage()], $exception->getMessage(), 422);
        }

        return ApiResponse::success(new ResultAppealResource($appeal), 'Appeal submitted.', 201);
    }

    public function updateStatus(UpdateResultAppealStatusRequest $request, ResultAppeal $appeal): JsonResponse
    {
        try {
            $appeal = app(AppealWorkflow::class)->resolve($appeal, $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            return ApiResponse::success(['error' => $exception->getMessage()], $exception->getMessage(), 422);
        }

        return ApiResponse::success(new ResultAppealResource($appeal), 'Appeal updated.');
    }
}