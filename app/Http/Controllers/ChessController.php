<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MoveMade;

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
}
