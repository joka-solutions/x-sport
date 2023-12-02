<?php

namespace App\Http\Controllers\Stadiums;

use App\Http\Controllers\Controller;
use App\Models\Stadium;
use Illuminate\Http\Request;

class StadiumController extends Controller
{
    public function get_stadiums(){
       $stadiums = Stadium::all();
        return response()->json($stadiums);
    }
}
