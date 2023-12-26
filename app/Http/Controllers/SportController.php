<?php

namespace App\Http\Controllers;

use App\Models\FavoritSports;
use App\Models\PreferenceOption;
use App\Models\Sport;
use App\Models\SportPreference;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;

class SportController extends Controller
{
    public function get_sport(){
        $sport = Sport::all();
        return response()->json($sport);
    }

    public function preferences(Request $request){


        $uses_option_id = $request->use_option_id;
        $postion_option_id = $request->postion_option_id;
        $time_option_id = $request->time_option_id;
        $sport_id = $request->sport_id;


        $token = $request->header('Authorization');
        $cleanToken = str_replace('Bearer ', '', $token);

        $user_token = Token::where('token', $cleanToken)->first();
        if(!isset($user_token)) {
            return response()->json(['message' => 'رمز التوكن  غير صحيح'], 400);

        }




        $user = User::where('id', $user_token->user_id)->with('token')->first();
        $sport_favourit = FavoritSports::where('user_id',$user->id)
                                        ->where('sport_id',$sport_id)
                                        ->first();
        if ($sport_favourit) {
            if ($uses_option_id){
                $sport_favourit->use_option_id = $uses_option_id;
            }

            if ($postion_option_id){
                $sport_favourit->postion_option_id = $postion_option_id;
            }

            if ($time_option_id){
                $sport_favourit->time_option_id = $time_option_id;
            }


            $sport_favourit->save();

            $data = $sport_favourit->load('use', 'postion', 'time');

            $use_favourit_id = $data->use->preference_id;
            $postion_favourit_id = $data->postion->preference_id;
            $time_favourit_id = $data->time->preference_id;

            $use_favourit_name = SportPreference::where('id', $use_favourit_id)->value('name');
            $postion_favourit_name = SportPreference::where('id', $postion_favourit_id)->value('name');
            $time_favourit_name = SportPreference::where('id', $time_favourit_id)->value('name');

            $favouirt = [
                'use_favourit' => [
                    'id' => $data->use->id,
                    'sport_id' => $data->use->sport_id,
                    'preference_id' => $data->use->preference_id,
                    'preference_name' => $use_favourit_name,
                    'name' => $data->use->name,

                ],
                'postion_favourit' => [
                    'id' => $data->postion->id,
                    'sport_id' => $data->postion->sport_id,
                    'preference_id' => $data->postion->preference_id,
                    'preference_name' => $postion_favourit_name,
                    'name' => $data->postion->name,

                ],
                'time_favourit' => [
                    'id' => $data->time->id,
                    'sport_id' => $data->time->sport_id,
                    'preference_id' => $data->time->preference_id,
                    'preference_name' => $time_favourit_name,
                    'name' => $data->time->name,

                ],
            ];





            return response()->json(
                [
                    'message' => 'تم تحديث البيانات بنجاح',
                    'preference_selected'=> $favouirt
                ], 200);
        } else {
            return response()->json(['message' => 'لا يوجد سجل FavouirtSports لهذا المستخدم وهذه الرياضة'], 404);
        }


    }
}
