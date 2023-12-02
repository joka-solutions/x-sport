<?php

namespace App\Http\Controllers\Matches;

use App\Http\Controllers\Controller;
use App\Models\Matche;
use Illuminate\Http\Request;

class CompetitiveMatchController extends Controller
{
    public function get_Competitive_matches(){

        $matches = Matche::with(['matcheType','sport','stadium','user','opponent'])->where('match_type_id',2)->where('opponent_id',null)->get();
        return response()->json($matches);
    }
}
