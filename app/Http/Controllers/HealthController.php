<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'time' => now()->toIso8601String(),
        ]);
    }
}
