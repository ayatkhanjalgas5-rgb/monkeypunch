<?php

namespace App\Http\Controllers;

use App\Models\Popup;

class PopupController extends Controller
{
    public function index()
    {
        $popup = Popup::inRandomOrder()->first();

        return response()->json($popup);
    }
}
