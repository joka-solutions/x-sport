<?php

namespace App\Http\Controllers\Matches;

use App\Http\Controllers\Controller;
use App\Models\Matche;
use App\Models\MatchType;
use Illuminate\Http\Request;

class FrindlyMatchController extends Controller
{
    public function get_metch_type(){
        $match_type = MatchType::all();
        return response()->json($match_type);
    }

    public function store_match(Request $request){

        $match = new Matche();

        $match->match_type_id = $request->match_type_id;
        $match->sport_id = $request->sport_id;
        $match->stadium_id = $request->stadium_id;
        $match->start_time = $request->start_time;
        $match->user_id = $request->user_id;
        $match->opponent_id = $request->opponent_id;

        $match->save();

        return response()->json(['message'=>'successfll','match'=>$match],200);
    }

    public function get_friendly_matches(){
        $matches = Matche::with(['matcheType','sport','stadium','user','opponent'])->where('match_type_id',1)->where('opponent_id',null)->get();
        return response()->json($matches);
    }





}
