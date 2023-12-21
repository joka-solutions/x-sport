<?php

namespace App\Http\Controllers\registeration;

use App\Http\Controllers\Controller;
use App\Models\FavoritSports;
use App\Models\Follower;
use App\Models\Matche;
use App\Models\Sport;
use App\Models\Token;
use App\Models\UserDetails;
use App\Models\UserLevel;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // return $request;
        // استقبال البيانات من طلب الإنشاء
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');
        $phone = $request->input('phone');
        $longitude = $request->longitude;
        $latitude = $request->latitude;

        $userExists = User::where('email', $email)->exists();
        if ($userExists) {
            return response()->json(['message' => 'البريد الالكتروني موجود سابقا'], 400);

        }
        // التحقق من تطابق كلمة المرور وتأكيد كلمة المرور
        if ($password !== $password_confirmation) {
            return response()->json(['message' => 'كلمة المرور وتأكيد كلمة المرور غير متطابقين'], 400);
        }

        // إنشاء حساب جديد
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $phone,
            'longitude' => $longitude,
            'latitude' => $latitude,
        ]);

        $user = User::where('email', $email)->first();


        $secretKey = Str::random(32);

        // توليد بيانات المستخدم الخاصة بالتوكن
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            // يمكنك تضمين المزيد من البيانات حسب الحاجة
        ];

        // توقيع التوكن باستخدام العبارة السرية
        $token = JWT::encode($userData, $secretKey);

        $user_token = new Token();

        $user_token->user_id = $user->id;
        $user_token->token = $token;

        $user_token->save();

        //$user_new = User::where('id', $user->id)->first();
        //$token = Token::where('user_id', $user->id)->first();


        $verificationCode = Str::random(6);
        $user->verification_code = $verificationCode;
        $user->is_verified = false;
        $user->save();


        try {
            Mail::raw("رمز التحقق الخاص بك هو:   $verificationCode", function ($message) use ($user) {
                $message->to($user->email)->subject('رمز التحقق');
            });

        } catch (Swift_TransportException $e) {
            return response()->json(['error' => 'حدث خطأ أثناء إرسال البريد الإلكتروني']);

        }

//        $data = [
//            'id' => $user->id,
//            'name' => $user->name,
//            'email' => $user->email,
//            'is_verified' => $user->is_verified,
//            'phone' => $user->phone,
//            'longitude' => $user->longitude,
//            'latitude' => $user->latitude,
//
//            'created_at'=>$user->created_at,
//            'updated_at'=>$user->updated_at
//        ];
//
//        $userFavorit = FavoritSports::where('user_id',$user->id)->get();
//
//
//        $favoritSports = [];
//        foreach ($userFavorit as $sportId) {
//            $sport = Sport::find($sportId->sport_id);
//
//            if ($sport) {
//                $user_details = UserDetails::find($user->id);
//
//                $level = UserLevel::find($user_details->level_id);
//
//                $favoritSports[] = [
//                    'sport_id' => $sport->id,
//                    'name' => $sport->name,
//                    'level' => $level ? $level->name : null,
//                    'point'=> $sportId->point,
//
//                ];
//            }
//        }
//
//        // إرجاع الاستجابة المناسبة (مثل رمز الاستجابة 200 ورسالة نجاح)
//        return response()->json([
//            'message' => 'تم إنشاء الحساب بنجاح',
//            'user' => $data,
//            'token'=>$token->token,
//            'favoritSports'=>$favoritSports
//        ], 200);

        $new_user = User::with(['token', 'followers', 'points', 'wallet'])->find($user->id);
        //return $new_user;
        $totalPoints = intval($new_user->points()->sum('point'));


        $matchCount = Matche::where('user_id', $new_user->id)
            ->orwhere('opponent_id', $new_user->id)
            ->whereNotNull('result')
            ->count();
        $image = $new_user->image;
        if ($image == null){
            $image = "";
        }



        $data = [
            'id' => $new_user->id,
            'name' => $new_user->name,
            'email' => $new_user->email,
            'is_verified' => $new_user->is_verified,
            'phone' => $new_user->phone,
            'longitude' => $new_user->longitude,
            'latitude' => $new_user->latitude,
            'image' => $image, // تحديث هناو
            'created_at' => $new_user->created_at,
            'updated_at' => $new_user->updated_at
        ];

        $userFavorit = FavoritSports::where('user_id', $user->id)->get();


        $favoritSports = [];
        foreach ($userFavorit as $sportId)
            $sport = Sport::find($sportId->sport_id);

            if (isset($sport)) {
                $user_details = UserDetails::find($user->id);

                $level = UserLevel::find($user_details->level_id);

                $favoritSports[] = [
                    'sport_id' => $sport->id,
                    'name' => $sport->name,
                    'level' => $level ? $level->name : null,
                    'point' => $sportId->point,


                ];

            }
                return response()->json(
                    [
                        'message' => 'تم تسجيل الدخول بنجاح',
                        'user' => $data,
                        'token' => $new_user->token->token,
                        'favorit_sports' => $favoritSports,
                        'followers' => $new_user->followers->count(),
                        'acadmies_points' => $totalPoints,
                        'wallet_point' => $new_user->wallet ? $new_user->wallet->point : 0,
                        'matchesCount' => $matchCount
                    ], 200);
            }



    public function verifyCode(Request $request)
    {
        $token = $request->header('Authorization');
        $cleanToken = str_replace('Bearer ', '', $token);

        $user_token = Token::where('token', $cleanToken)->first();
        if(!isset($user_token)) {
            return response()->json(['message' => 'رمز التوكن  غير صحيح'], 400);

        }
        $user = User::where('id', $user_token->user_id)->with('token')->first();


        // التحقق من صحة رمز التحقق
        if ($user->verification_code === $request->verification_code) {
            // تحديث حالة التحقق للمستخدم
            $user->is_verified = true;
            $user->save();

            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_verified' => $user->is_verified,
                'phone' => $user->phone,
                'longitude' => $user->longitude,
                'latitude' => $user->latitude,
                'image' => url($user->image), // تحديث هناو
                'created_at'=>$user->created_at,
                'updated_at'=>$user->updated_at
            ];

            return response()->json(
                [
                    'message' => 'تم التحقق بنجاح',
                    'user' => $data,
                    'token'=>$user_token->token
                ]);
        } else {
            return response()->json(['message' => 'رمز التحقق غير صحيح'], 400);
        }
    }



    public function login(Request $request)
    {


            $credentials = $request->only('email_or_phone', 'password');

        if (filter_var($credentials['email_or_phone'], FILTER_VALIDATE_EMAIL)) {
            $authField = 'email'; // البريد الإلكتروني
        } else {
            $authField = 'phone'; // رقم الهاتف
        }

        // محاولة تسجيل الدخول باستخدام البيانات المُقدمة
        if (Auth::attempt([$authField => $credentials['email_or_phone'], 'password' => $credentials['password']])) {
            // التحقق من نجاح تسجيل الدخول واسترجاع بيانات المستخدم
            $user = Auth::user();

            $new_user = User::with(['token','followers','points','wallet'])->find($user->id);
            //return $new_user;
            $totalPoints = intval($new_user->points()->sum('point'));



            $matchCount = Matche::where('user_id',$new_user->id)
                             ->orwhere('opponent_id',$new_user->id)
                             ->whereNotNull('result')
                             ->count();

            $image = $new_user->image;
            if ($image == null){
                $image = "";
            }else{
                $image = url( $new_user->image);
            }


            $data = [
                'id' => $new_user->id,
                'name' => $new_user->name,
                'email' => $new_user->email,
                'is_verified' => $new_user->is_verified,
                'phone' => $new_user->phone,
                'longitude' => $new_user->longitude,
                'latitude' => $new_user->latitude,
                'image' => $image, // تحديث هناو
                'created_at'=>$new_user->created_at,
                'updated_at'=>$new_user->updated_at
            ];

//            $userFavorit = FavoritSports::where('user_id',$user->id)->get();
//
//
//            $favoritSports = [];
//            foreach ($userFavorit as $sportId) {
//                $sport = Sport::find($sportId->sport_id);
//
//                if (isset($sport)) {
//                    $user_details = UserDetails::find($user->id);
//
//                    $level = UserLevel::find($user_details->level_id);
//
//                    $favoritSports[] = [
//                        'sport_id' => $sport->id,
//                        'name' => $sport->name,
//                        'level' => $level ? $level->name : null,
//                        'point'=> $sportId->point,
//
//
//                    ];
//                }
//            }
//
//            return response()->json(
//                [
//                    'message' => 'تم تسجيل الدخول بنجاح',
//                    'user' => $data,
//                    'token'=>$new_user->token->token,
//                    'favorit_sports' => $favoritSports,
//                    'followers'=>$new_user->followers->count(),
//                    'acadmies_points'=>$totalPoints ,
//                    'wallet_point'=>$new_user->wallet ? $new_user->wallet->point : 0,
//                    'matchesCount'=>$matchCount
//                ], 200);



//            $userFavorit = FavoritSports::where('user_id', $user->id)->get();
//            $favoritSports = [];
//
//            foreach ($userFavorit as $sportId) {
//                $sport = Sport::find($sportId->sport_id);
//
//                if (isset($sport)) {
//                    $user_details = UserDetails::find($user->id);
//                    $level = UserLevel::find($user_details->level_id);
//
//                    // Load preferences and options for the current sport
//                    $sport->load('preferences.options');
//
//                    $sportDetails = [
//                        'sport_id' => $sport->id,
//                        'name' => $sport->name,
//                        'level' => $level ? $level->name : null,
//                        'point' => $sportId->point,
//                        'preferences' => $sport->preferences->map(function ($preference) {
//                            return [
//                                'id' => $preference->id,
//                                'name' => $preference->name,
//                                'options' => $preference->options->map(function ($option) {
//                                    return [
//                                        'id' => $option->id,
//                                        'name' => $option->name,
//                                        // يمكنك إضافة المزيد من البيانات هنا إذا أردت
//                                    ];
//                                })
//                            ];
//                        })
//                    ];
//
//                    $favoritSports[] = $sportDetails;
//                }
//            }

    // get sport favouirts with preference selected
            $userFavorit = FavoritSports::where('user_id', $user->id)->with(['use', 'postion', 'time'])->get();
            $favoritSports = [];

            foreach ($userFavorit as $sportId) {
                $sport = Sport::find($sportId->sport_id);

                if ($sport) {
                    $user_details = UserDetails::find($user->id);
                    $level = UserLevel::find($user_details->level_id);

                    $sport = $sport->load('preferences.options');

                    $sportDetails = [
                        'sport_id' => $sport->id,
                        'name' => $sport->name,
                        'level' => $level ? $level->name : null,
                        'point' => $sportId->point,
                        'preferences' => $sport->preferences->map(function ($preference) use ($sportId) {
                            $selected = null;
                            switch ($preference->name) {
                                case 'اليد المفضلة':
                                    $selected = $sportId->use ? $sportId->use->name : "";
                                    break;
                                case 'المركز المفضل':
                                    $selected = $sportId->postion ? $sportId->postion->name : "";
                                    break;
                                case 'الوقت المفضل':
                                    $selected = $sportId->time ? $sportId->time->name : "";
                                    break;
                                default:
                                    break;
                            }

                            return [
                                'id' => $preference->id,
                                'name' => $preference->name,
                                'selected' => $selected,
                                'options' => $preference->options->map(function ($option) {
                                    return [
                                        'id' => $option->id,
                                        'name' => $option->name,
                                    ];
                                }),
                            ];
                        }),
                    ];

                    $favoritSports[] = $sportDetails;
                }
            }


            return response()->json([
                'message' => 'تم تسجيل الدخول بنجاح',
                'user' => $data,
                'token' => $new_user->token->token,
                'favorit_sports' => $favoritSports,
                'followers' => $new_user->followers->count(),
                'acadmies_points' => $totalPoints,
                'wallet_point' => $new_user->wallet ? $new_user->wallet->point : 0,
                'matchesCount' => $matchCount
            ], 200);

        } else {
            // رسالة خطأ في حالة فشل عملية تسجيل الدخول
            return response()->json(['message' => 'بيانات تسجيل الدخول غير صحيحة'], 401);
        }
    }


    public function registerWithGoogle(Request $request){

        $userExists = User::where('email', $request->email)->exists();
        if ($userExists) {
            return response()->json(['message' => 'البريد الالكتروني موجود سابقا'], 400);

        }

        $newUser = new User();

        $newUser->name= $request->name;
        $newUser->email= $request->email;
        $newUser->longitude= $request->longitude;
        $newUser->latitude= $request->latitude;
        $newUser->password= bcrypt('my-google');
        $newUser->is_verified= false;

        $newUser->save();


        $user = User::where('email',$request->email)->first();



        $secretKey = Str::random(32);

        // توليد بيانات المستخدم الخاصة بالتوكن
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            // يمكنك تضمين المزيد من البيانات حسب الحاجة
        ];

        // توقيع التوكن باستخدام العبارة السرية
        $token = JWT::encode($userData, $secretKey);

        $user_token = new Token();

        $user_token->user_id = $user->id;
        $user_token->token = $token;

        $user_token->save();

        $new_user = User::with(['token','followers','points','wallet'])->find($user->id);
        //return $new_user;
        $totalPoints = intval($new_user->points()->sum('point'));



        $matchCount = Matche::where('user_id',$new_user->id)
            ->orwhere('opponent_id',$new_user->id)
            ->whereNotNull('result')
            ->count();




        $data = [
            'id' => $new_user->id,
            'name' => $new_user->name,
            'email' => $new_user->email,
            'is_verified' => $new_user->is_verified,
            'phone' => $new_user->phone,
            'longitude' => $new_user->longitude,
            'latitude' => $new_user->latitude,
            'image' => url($new_user->image), // تحديث هناو
            'created_at'=>$new_user->created_at,
            'updated_at'=>$new_user->updated_at
        ];

        $userFavorit = FavoritSports::where('user_id',$user->id)->get();


        $favoritSports = [];
        foreach ($userFavorit as $sportId) {
            $sport = Sport::find($sportId->sport_id);

            if (isset($sport)) {
                $user_details = UserDetails::find($user->id);

                $level = UserLevel::find($user_details->level_id);

                $favoritSports[] = [
                    'sport_id' => $sport->id,
                    'name' => $sport->name,
                    'level' => $level ? $level->name : null,
                    'point'=> $sportId->point,


                ];
            }
        }

        return response()->json(
            [
                'message' => 'تم تسجيل الدخول بنجاح',
                'user' => $data,
                'token'=>$new_user->token->token,
                'favorit_sports' => $favoritSports,
                'followers'=>$new_user->followers->count(),
                'acadmies_points'=>$totalPoints ,
                'wallet_point'=>$new_user->wallet ? $new_user->wallet->point : 0,
                'matchesCount'=>$matchCount
            ], 200);
    }
}
