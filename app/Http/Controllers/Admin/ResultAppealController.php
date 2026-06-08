<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResultAppealRequest;
use App\Http\Requests\Admin\UpdateResultAppealStatusRequest;
use App\Models\Event;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Support\AppealWorkflow;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ResultAppealController extends Controller
{
    public function store(
        StoreResultAppealRequest $request,
        Event $event,
        Result $result,
    ): RedirectResponse {
        abort_unless($result->match?->event()?->id === $event->id, 404);
        $this->authorize('create', [ResultAppeal::class, $result]);

        try {
            app(AppealWorkflow::class)->submit($result, $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['appeal' => $exception->getMessage()]);
        }

        return back()->with('success', 'Appeal submitted.');
    }

    public function updateStatus(
        UpdateResultAppealStatusRequest $request,
        Event $event,
        ResultAppeal $appeal,
    ): RedirectResponse {
        abort_unless($appeal->result?->match?->event()?->id === $event->id, 404);

        try {
            app(AppealWorkflow::class)->resolve($appeal, $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['appeal' => $exception->getMessage()]);
        }

        return back()->with('success', 'Appeal updated.');
    }
}