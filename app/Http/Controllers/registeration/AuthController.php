<?php

namespace App\Http\Controllers\registeration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // استقبال البيانات من طلب الإنشاء
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');
        $phone = $request->input('phone');
        $location = $request->input('location');

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
            'location' => $location
        ]);

        // إرجاع الاستجابة المناسبة (مثل رمز الاستجابة 200 ورسالة نجاح)
        return response()->json(['message' => 'تم إنشاء الحساب بنجاح'], 200);
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
}
