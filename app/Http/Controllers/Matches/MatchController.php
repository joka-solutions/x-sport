<?php

namespace App\Http\Controllers\Matches;

use App\Http\Controllers\Controller;
use App\Models\Matche;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function join_match(Request $request ,$id){
        $match = Matche::find($id);
        $match->opponent_id = $request->opponent_id;
        $match->save();
        return response()->json(['message'=>'تم اضافة الطلب بنجاح','match'=>$match],200);
    }
}
