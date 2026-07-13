<?php

namespace App\Http\Controllers;

use App\Events\CaroMoveMade;
use Illuminate\Http\Request;

class CaroController extends Controller
{
    public function index()
    {
        return view('caro.index');
    }

    public function play($roomId)
    {
        return view('caro.play', compact('roomId'));
    }

    public function broadcastMove(Request $request, $roomId)
    {
        broadcast(new CaroMoveMade($roomId, $request->move));

        return response()->json(['status' => 'success']);
    }
}
