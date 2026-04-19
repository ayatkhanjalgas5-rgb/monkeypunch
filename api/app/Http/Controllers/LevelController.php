<?php

namespace App\Http\Controllers;

use App\Models\Level;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $levels = Level::all();

        return response()->json($levels);
    }
}
