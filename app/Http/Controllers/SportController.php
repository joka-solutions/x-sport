<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use Illuminate\Http\Request;

class SportController extends Controller
{
    public function get_sport(){
        $sport = Sport::all();
        return response()->json($sport);
    }
}
