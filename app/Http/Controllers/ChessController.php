<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MoveMade;

class ChessController extends Controller
{
    // Hiển thị trang chủ cờ vua (nơi tạo phòng)
    public function index()
    {
        return view('chess.index');
    }

    // Vào phòng chơi cụ thể
    public function play($roomId)
    {
        return view('chess.play', compact('roomId'));
    }

    public function broadcastMove(Request $request, $roomId)
    {
        // Tạm thời bỏ ->toOthers() đi để test gửi cho tất cả
        broadcast(new MoveMade($roomId, $request->move));

        return response()->json(['status' => 'Move broadcasted']);
    }
}
