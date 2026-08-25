<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('wallet.{walletId}', function ($user, $walletId) {
    return $user->wallets()->where('id', $walletId)->exists();
});

Broadcast::channel('admin.float-alerts', function ($user) {
    return $user->hasRole(['admin', 'super_admin']);
});

Broadcast::channel('admin.disputes', function ($user) {
    return $user->hasRole(['admin', 'super_admin']);
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return $user->id === $userId;
});
