<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chess.{roomId}', function ($user, $roomId) {
    $cacheKey = "chess_room_{$roomId}_players";
    $players = Cache::get($cacheKey, []);

    // Phòng đã đủ 2 người VÀ user này chưa từng vào phòng → từ chối
    if (count($players) >= 2 && !isset($players[$user->id])) {
        return null; // 403 - Từ chối
    }

    // Lưu user vào danh sách players (dùng id làm key để tránh trùng)
    $players[$user->id] = [
        'id' => $user->id,
        'name' => $user->name,
    ];

    Cache::put($cacheKey, $players, now()->addHours(2));

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

Broadcast::channel('caro.{roomId}', function ($user, $roomId) {
    $cacheKey = "caro_room_{$roomId}_players";
    $players = Cache::get($cacheKey, []);

    // Áp dụng logic tương tự cho caro nếu cần
    if (count($players) >= 2 && !isset($players[$user->id])) {
        return null;
    }

    $players[$user->id] = [
        'id' => $user->id,
        'name' => $user->name,
    ];

    Cache::put($cacheKey, $players, now()->addHours(2));

    return ['id' => $user->id, 'name' => $user->name];
});
