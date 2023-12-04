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


        $verificationCode = Str::random(6);
        $user->verification_code = $verificationCode;
        $user->is_verified = false;
        $user->save();

        Mail::raw("رمز التحقق الخاص بك هو:   $verificationCode", function ($message) use ($user) {
            $message->to($user->email)->subject('رمز التحقق');
        });

        // إرجاع الاستجابة المناسبة (مثل رمز الاستجابة 200 ورسالة نجاح)
        return response()->json(['message' => 'تم إنشاء الحساب بنجاح', 'token' => $token, 'data' => $user], 200);
    }

    public function verifyCode(Request $request)
    {
        $token = $request->header('Authorization');
        $cleanToken = str_replace('Bearer ', '', $token);

        $user_token = Token::where('token', $cleanToken)->first();
        $user = User::where('id', $user_token->user_id)->first();


        // التحقق من صحة رمز التحقق
        if ($user->verification_code === $request->verification_code) {
            // تحديث حالة التحقق للمستخدم
            $user->is_verified = true;
            $user->save();

            return response()->json(['message' => 'تم التحقق بنجاح', 'token' => $cleanToken, 'data' => $user]);
        } else {
            return response()->json(['message' => 'رمز التحقق غير صحيح'], 400);
        }
    }

    public function login(Request $request)
    {
        // استقبال بيانات تسجيل الدخول
        $emailOrPhone = $request->input('email_or_phone');
        $password = $request->input('password');

        // تحقق من صحة بيانات تسجيل الدخول
        if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
            // إذا كان البريد الإلكتروني صالحًا، استخدمه كمعرّف
            $credentials = [
                'email' => $emailOrPhone,
                'password' => $password,
            ];
        } else {
            // إذا لم يكن البريد الإلكتروني صالحًا، استخدم رقم الهاتف كمعرّف
            $credentials = [
                'phone' => $emailOrPhone,
                'password' => $password,
            ];
        }

        // تحقق من صحة بيانات تسجيل الدخول
        if (Auth::validate($credentials)) {
            // تم التحقق بنجاح
            return response()->json(['message' => 'تم تسجيل الدخول بنجاح'], 200);
        } else {
            // عند فشل التحقق من صحة بيانات تسجيل الدخول
            return response()->json(['message' => 'بيانات تسجيل الدخول غير صحيحة'], 401);
        }
    }

    public function registerWithGoogle(Request $request){

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

        // إرجاع التوكن في استجابة التسجيل
        return response()->json([
            'message' => 'تم تسجيل المستخدم بنجاح.',
            'token' => $token,
            'user_id' => $user->id,
            'user'=>$newUser,

        ]);
    }
}
