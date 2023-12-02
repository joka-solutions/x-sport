<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function get_users(){
        $users = User::with(['details'])->get();
        return response()->json($users);
    }
    public function store(Request $request){
        $userId = $request->input('user_id');
        $sportId = $request->input('sport_id');
        $levelId = $request->input('level_id');
        $profileImage = $request->file('profile_image');

        // قم بتخزين البيانات في قاعدة البيانات أو العمليات اللازمة

        // مثال: تخزين الصورة في مجلد "public/images"
        $imagePath = $profileImage->storePublicly('/images');

       $data = new UserDetails();
        $data->user_id= $userId;
        $data->sport_id= $sportId;
        $data->level_id= $levelId;
        $data->image= $imagePath;

        $data->save();

        // قم بإرجاع رد الاستجابة المناسبة
        return response()->json([
            'message' => 'تم تخزين بيانات المستخدم بنجاح.',
            'user_id' => $userId,
            'sport_id' => $sportId,
            'level_id' => $levelId,
            'image_path' => $imagePath,
        ], 200);
    }

    public function update_profile(Request $request ,$id){

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود.'], 404);
        }

        // تحديث حقول الجدول users
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');
        $user->location = $request->input('location');

        // حفظ التغييرات في جدول users
        $user->save();

        // تحديث حقول الجدول user_details
        $userDetails = UserDetails::where('user_id', $id)->first();

//        if (!$userDetails) {
//            return response()->json(['message' => 'تفاصيل المستخدم غير موجودة.'], 404);
//        }
        if ($userDetails) {

            $userDetails->sport_id = $request->input('sport_id');
            $userDetails->image = $request->input('image');
            $userDetails->level_id = $request->input('level_id');

            // حفظ التغييرات في جدول user_details
            $userDetails->save();
        }

        return response()->json(['message' => 'تم تحديث البيانات بنجاح.'], 200);
    }

}
