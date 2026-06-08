<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResultRequest;
use App\Http\Requests\Admin\UpdateResultStatusRequest;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\MatchGame;
use App\Models\Result;
use App\Support\ResultWorkflow;
use Illuminate\Http\RedirectResponse;

class ResultController extends Controller
{
    public function store(
        StoreResultRequest $request,
        Event $event,
        Competition $competition,
        Fixture $fixture,
        MatchGame $matchGame,
    ): RedirectResponse {
        $this->ensureScope($event, $competition, $fixture, $matchGame);

        app(ResultWorkflow::class)->record($matchGame, $request->validated(), $request->user());

        return back()->with('success', 'Result recorded.');
    }

    public function updateStatus(UpdateResultStatusRequest $request, Event $event, Result $result): RedirectResponse
    {
        abort_unless($result->match?->event()?->id === $event->id, 404);

        app(ResultWorkflow::class)->advanceStatus(
            $result,
            $request->validated('status'),
            $request->user(),
        );

        return back()->with('success', 'Result status updated.');
    }

    private function ensureScope(Event $event, Competition $competition, Fixture $fixture, MatchGame $matchGame): void
    {
        abort_unless($competition->event_id === $event->id, 404);
        abort_unless($fixture->competition_id === $competition->id, 404);
        abort_unless($matchGame->fixture_id === $fixture->id, 404);
    }
}