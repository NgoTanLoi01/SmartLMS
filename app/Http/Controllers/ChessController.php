<?php

namespace App\Http\Controllers;

use App\Events\MoveMade;
use Illuminate\Http\Request;

class ChessController extends Controller
{
    public function index()
    {
        return view('chess.index');
    }

    public function play($roomId)
    {
        return view('chess.play', compact('roomId'));
    }

    public function broadcastMove(Request $request, $roomId)
    {
        broadcast(new MoveMade($roomId, $request->move));

        return response()->json(['status' => 'Move broadcasted']);
    }

    public function finish($roomId)
    {
        Cache::forget("chess_room_{$roomId}_players");

        return response()->json(['success' => true]);
    }
}
