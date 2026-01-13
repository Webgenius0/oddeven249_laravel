<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class DashBoardController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all();
        return view('backend.layouts.index');
    }
}
