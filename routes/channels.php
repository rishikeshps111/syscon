<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId || $user->hasRole('Super Admin');
});

Broadcast::channel('chat.admin', function ($user) {
    return $user->hasRole('Super Admin');
});

Broadcast::channel('license-alert.user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId && $user->can('driver-management.view');
});
