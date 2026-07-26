<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SystemController extends Controller
{
    public function index()
    {
        $dbName = config('database.connections.mysql.database');

        $dbSize = DB::select('SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
            FROM information_schema.tables 
            WHERE table_schema = ?', [$dbName]);

        return Inertia::render('Admin/System/Index', [
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => \Illuminate\Foundation\Application::VERSION,
            'dbSize' => $dbSize[0]->size_mb.' MB',
            'serverTime' => now()->toDateTimeString(),
        ]);
    }
}
