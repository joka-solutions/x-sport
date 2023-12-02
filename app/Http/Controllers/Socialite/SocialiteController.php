<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToGoogle(){
        return Socialite::driver('google')->redirect();
    }

    public function redirectToFacebook(){
        return Socialite::driver('facebook')->redirect();
    }

    public function handleGoogleCallback(){
        try {


        $user = Socialite::driver('google')->user();
        $finduser= User::where('email',$user->getEmail())->first();

        if ($finduser){
            Auth::login($finduser);
            Session::put('user', $finduser);
            return response()->json(['msg'=>$finduser],200);

        }

        else{
            $newUser = new User();

            $newUser->name= $user->name;
            $newUser->email= $user->email;
            $newUser->social_id= $user->id;
            $newUser->social_type= 'google';
            $newUser->password= encrypt('my-google');

            $newUser->save();

            return \response()->json(['data'=>$newUser]);

        }


    }
    catch (\Exception $e){
            return \response()->json($e->getMessage());
        }
    }

}
