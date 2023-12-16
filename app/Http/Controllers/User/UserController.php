<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FavoritSports;
use App\Models\Token;
use App\Models\User;
use App\Models\UserDetails;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;

class UserController extends Controller
{
    public function get_users(){
        $users = User::with(['details'])->get();
        return response()->json($users);
    }




    public function store(Request $request)
    {
        $token = $request->header('Authorization');
        $cleanToken = str_replace('Bearer ', '', $token);

        $user_token = Token::where('token', $cleanToken)->first();
        if(!isset($user_token)) {
            return response()->json(['message' => 'رمز التوكن  غير صحيح'], 400);

        }
        //return $request;
        // ... الرموز الخاصة بالمستخدم والأنواع ...
        $userId = $request->user_id;
        $sportId = $request->sport_id;
        $levelId = $request->level_id;
        $type = $request->type;

        $selectedSports = $request->input('selected_sports'); // اسم الحقل المرسل من الفورم

        // استقبال الصورة كبيانات ثنائية من الطلب في Postman
        $file = $request->file('profile_image');

        // حصول معلومات الملف
        $binaryImage = file_get_contents($file->getRealPath());

        // حدد المسار الكامل لمجلد الصور
        $imageFolderPath = public_path('images');

        // تأكد من وجود المجلد، وإن لم يكن فأنشئه
        if (!file_exists($imageFolderPath)) {
            mkdir($imageFolderPath, 0777, true);
        }
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        // التحقق من أن الامتداد مسموح
        if (!in_array($type, $allowedExtensions)) {
            return response()->json([
                'message' => 'صيغة الصورة غير مدعومة. يرجى تحميل صورة بصيغة مدعومة مثل JPG أو PNG أو GIF.',
            ], 400);
        }
        // حفظ الصورة في المسار المحدد
        $fileName = uniqid() . '.' . $type;
        $filePath = $imageFolderPath . '/' . $fileName;

        file_put_contents($filePath, $binaryImage);

        // قم بتخزين مسار الصورة في قاعدة البيانات أو العمليات اللازمة
        // ...
       // $selectedSports=[1,2,4];
        foreach ($selectedSports as $sportId) {
            $favoritSports = new FavoritSports();

            $favoritSports->user_id= $userId;
            $favoritSports->sport_id= $sportId;

            $favoritSports->save();

          //  $userDetail->favoriteSports()->attach($sportId, ['user_id' => $userId]);
        }


        $data = new UserDetails();
        $data->user_id= $userId;
        $data->sport_id= $sportId;
        $data->level_id= $levelId;
        $data->image= 'images/'.$fileName;

        $data->save();

        $user= User::where('id',$userId)->with(['token','details','favoritSports'])->get();

        // قم بإرجاع رد الاستجابة المناسبة
        return response()->json([
            'message' => 'تم تخزين بيانات المستخدم بنجاح.',
            'user' => $user
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
