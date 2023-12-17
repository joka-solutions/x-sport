<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FavoritSports;
use App\Models\Matche;
use App\Models\Sport;
use App\Models\Token;
use App\Models\User;
use App\Models\UserDetails;

use App\Models\UserLevel;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;

class UserController extends Controller
{
    public function get_users(){
        $users = User::with(['details'])->get();
        return response()->json($users);
    }

    public function getUserWithToken(Request $request)
    {
        $token = $request->header('Authorization');
        $cleanToken = str_replace('Bearer ', '', $token);

        // فحص صحة رمز التوكن
        $user_token = Token::where('token', $cleanToken)->first();

        if (!isset($user_token)) {
            return response()->json(['message' => 'رمز التوكن غير صحيح'], 400);
        }


        $userId = $user_token->user_id;
        $new_user = User::with(['token', 'followers', 'points', 'wallet'])->find($userId);
        //return $new_user;
        $totalPoints = intval($new_user->points()->sum('point'));


        $matchCount = Matche::where('user_id', $new_user->id)
            ->orwhere('opponent_id', $new_user->id)
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
            'created_at' => $new_user->created_at,
            'updated_at' => $new_user->updated_at
        ];

        $userFavorit = FavoritSports::where('user_id', $userId)->get();


        $favoritSports = [];
        foreach ($userFavorit as $sportId) {
            $sport = Sport::find($sportId->sport_id);

            if (isset($sport)) {
                $user_details = UserDetails::find($userId);

                $level = UserLevel::find($user_details->level_id);

                $favoritSports[] = [
                    'sport_id' => $sport->id,
                    'name' => $sport->name,
                    'level' => $level ? $level->name : null,
                    'point' => $sportId->point,


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


    public function store(Request $request)
    {
        $token = $request->header('Authorization');
        $cleanToken = str_replace('Bearer ', '', $token);

        // فحص صحة رمز التوكن
        $user_token = Token::where('token', $cleanToken)->first();
        if (!isset($user_token)) {
            return response()->json(['message' => 'رمز التوكن غير صحيح'], 400);
        }


        $userId = $user_token->user_id;
        //$level_id =1;

        $selectedSports = $request->input('selected_sports');

        $file = $request->file('profile_image');
        $binaryImage = file_get_contents($file->getRealPath());

        $imageFolderPath = public_path('images');
        if (!file_exists($imageFolderPath)) {
            mkdir($imageFolderPath, 0777, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $type = $file->getClientOriginalExtension();

        if (!in_array($type, $allowedExtensions)) {
            return response()->json([
                'message' => 'صيغة الصورة غير مدعومة. يرجى تحميل صورة بصيغة مدعومة مثل JPG أو PNG أو GIF.',
            ], 400);
        }

        $fileName = uniqid() . '.' . $type;
        $filePath = $imageFolderPath . '/' . $fileName;
        file_put_contents($filePath, $binaryImage);

        $user = User::where('id', $userId)->first();
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
        $data->level_id= 1;


        $data->save();

        $userImage = User::findOrFail($userId);
        $userImage->image = 'images/'.$fileName;
        $userImage->save();

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_verified' => $user->is_verified,
            'phone' => $user->phone,
            'longitude' => $user->longitude,
            'latitude' => $user->latitude,
            'image' => url('images/' . $fileName), // تحديث هناو
            'created_at'=>$user->created_at,
            'updated_at'=>$user->updated_at
        ];

        $favoritSports = [];
        foreach ($selectedSports as $sportId) {

            $sport = Sport::find($sportId);
            if ($sport) {
                $user_details = UserDetails::find($userId);
                $level = UserLevel::find($user_details->level_id);

                $favoritSports[] = [
                    'sport_id' => $sport->id,
                    'name' => $sport->name,
                    'level' => $level ? $level->name : null,
                    'point'=> 0,
                ];
            }
        }
//        $level_id = UserDetails::where('user_id',$userId)->get();
//        $level = [];
//        foreach ($level_id as $levelId) {
//            $levels = UserLevel::find($levelId->level_id);
//            if ($levels) {
//                $level[] = [
//                    'level_id' => $levelId->level_id,
//                    'name' => $levels->name,
//                ];
//            }
//        }

        $response = [
            'message' => 'تم تخزين بيانات المستخدم بنجاح.',
            'user' => $data,
            'token' => $user_token->token,
            'favorit_sports' => $favoritSports,

        ];

        return response()->json($response, 200);
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
