<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('events.{event}.results', function (User $user, Event $event) {
    return $user->canViewResults() && $user->can('view', $event);
});