<?php

namespace App\Http\Controllers\registeration;

use App\Http\Controllers\Controller;
use App\Models\Token;
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

        $user_new = User::where('id', $user->id)->with('token')->first();


        $verificationCode = Str::random(6);
        $user->verification_code = $verificationCode;
        $user->is_verified = false;
        $user->save();


        try{
        Mail::raw("رمز التحقق الخاص بك هو:   $verificationCode", function ($message) use ($user) {
            $message->to($user->email)->subject('رمز التحقق');
        });

        } catch (Swift_TransportException $e) {
            return response()->json(['error' => 'حدث خطأ أثناء إرسال البريد الإلكتروني']);

        }

        // إرجاع الاستجابة المناسبة (مثل رمز الاستجابة 200 ورسالة نجاح)
        return response()->json([
            'message' => 'تم إنشاء الحساب بنجاح',

            'data' => $user_new
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

            return response()->json(
                [
                    'message' => 'تم التحقق بنجاح',
                    'data' => $user
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

            $new_user = User::with('token')->find($user->id);

            //$token = Token::where('user_id',$user->id)->first();

            return response()->json(
                [
                    'message' => 'تم تسجيل الدخول بنجاح',

                    'user' => $new_user
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
        $newUser->password= encrypt('my-google');
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

        $new_user= User::with('token')->find($user->id);

        // إرجاع التوكن في استجابة التسجيل
        return response()->json([
            'message' => 'تم تسجيل المستخدم بنجاح.',

            'user'=>$new_user,

        ]);
    }
}
