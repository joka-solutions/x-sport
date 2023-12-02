<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserLevel;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    public function get_user_level(){
        $level = UserLevel::all();
        return response()->json($level);
    }
}
