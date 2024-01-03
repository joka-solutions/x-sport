<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FavoritSports;
use App\Models\Matche;
use App\Models\PreferenceOption;
use App\Models\Sport;
use App\Models\SportPreference;
use App\Models\Token;
use App\Models\User;
use App\Models\UserDetails;

use App\Models\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'gender' => $new_user->gender,
            'longitude' => $new_user->longitude,
            'latitude' => $new_user->latitude,
            'image' => url($new_user->image), // تحديث هناو
            'created_at' => $new_user->created_at,
            'updated_at' => $new_user->updated_at
        ];

//        $userFavorit = FavoritSports::where('user_id', $userId)->get();
//
//
//        $favoritSports = [];
//        foreach ($userFavorit as $sportId) {
//            $sport = Sport::find($sportId->sport_id);
//
//            if (isset($sport)) {
//                $user_details = UserDetails::find($userId);
//
//                $level = UserLevel::find($user_details->level_id);
//
//                $favoritSports[] = [
//                    'sport_id' => $sport->id,
//                    'name' => $sport->name,
//                    'level' => $level ? $level->name : null,
//                    'point' => $sportId->point,
//
//
//                ];
//
//            }
//        }

        $userFavorit = FavoritSports::where('user_id', $userId)->with(['use','postion','time'])->get();
       // return $userFavorit;
        $favoritSports = [];

        foreach ($userFavorit as $sportId) {
            $sport = Sport::find($sportId->sport_id);

            if (isset($sport)) {
                $user_details = UserDetails::find($userId);
                $level = UserLevel::find($user_details->level_id);

                // Load preferences and options for the current sport
                $sport->load('preferences.options');

                $sportDetails = [
                    'sport_id' => $sport->id,
                    'name' => $sport->name,
                    'level' => $level ? $level->name : null,
                    'point' => $sportId->point,
                    'preferences' => $sport->preferences->map(function ($preference) {
                        return [
                            'id' => $preference->id,
                            'name' => $preference->name,
                            'options' => $preference->options->map(function ($option) {
                                return [
                                    'id' => $option->id,
                                    'name' => $option->name,
                                    // يمكنك إضافة المزيد من البيانات هنا إذا أردت
                                ];
                            })
                        ];
                    })
                ];

                $favoritSports[] = $sportDetails;
            }
        }
      //  return $favoritSports;

        //get preference options for all sport for user



//        $userFavourites = FavoritSports::where('user_id', $userId)->get();
//        $favourites = [];
//
//        foreach ($userFavourites as $favourite) {
//            $data2 = [];
//
//            if ($favourite->use) {
//                $data2['use_favourit'] = [
//                    'id' => $favourite->use->id,
//                    'sport_id' => $favourite->use->sport_id,
//                    'preference_id' => $favourite->use->preference_id,
//                    'preference_name' => $favourite->use->preference ? $favourite->use->preference->name : "",
//                    'name' => $favourite->use->name,
//                    'created_at' => $favourite->use->created_at,
//                    'updated_at' => $favourite->use->updated_at,
//                ];
//            } else {
//                $data2['use_favourit'] = " ";
//            }
//
//            if ($favourite->postion) {
//                $data2['postion_favourit'] = [
//                    'id' => $favourite->postion->id,
//                    'sport_id' => $favourite->postion->sport_id,
//                    'preference_id' => $favourite->postion->preference_id,
//                    'preference_name' => $favourite->postion->preference ? $favourite->postion->preference->name : "",
//                    'name' => $favourite->postion->name,
//                    'created_at' => $favourite->postion->created_at,
//                    'updated_at' => $favourite->postion->updated_at,
//                ];
//            } else {
//                $data2['postion_favourit'] = " ";
//            }
//
//            if ($favourite->time) {
//                $data2['time_favourit'] = [
//                    'id' => $favourite->time->id,
//                    'sport_id' => $favourite->time->sport_id,
//                    'preference_id' => $favourite->time->preference_id,
//                    'preference_name' => $favourite->time->preference ? $favourite->time->preference->name : "",
//                    'name' => $favourite->time->name,
//                    'created_at' => $favourite->time->created_at,
//                    'updated_at' => $favourite->time->updated_at,
//                ];
//            } else {
//                $data2['time_favourit'] = " ";
//            }
//
//            $favourites[] = $data2;
//        }

        //return response()->json(['prference_selcted'=>$favourites ]);

//////new code
        $userFavorit = FavoritSports::where('user_id', $userId)->with(['use', 'postion', 'time'])->get();
        $favoritSports = [];

        foreach ($userFavorit as $sportId) {
            $sport = Sport::find($sportId->sport_id);

            if ($sport) {
                $user_details = UserDetails::find($userId);
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

       // return $favoritSports;



        ///////////////////////////



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
        //$type = $file->getClientOriginalExtension();
        $type = $request->input('type');

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
            $existingFavoriteSport = FavoritSports::where('user_id', $userId)->where('sport_id', $sportId)->first();

            if (!$existingFavoriteSport) {
                $favoritSports = new FavoritSports();
                $favoritSports->user_id = $userId;
                $favoritSports->sport_id = $sportId;
                $favoritSports->save();

                $data = new UserDetails();
                $data->user_id= $userId;
                $data->sport_id= $sportId;
                $data->level_id= 1;


                $data->save();
            }
        }




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

//        $favoritSports = [];
//        foreach ($selectedSports as $sportId) {
//
//            $sport = Sport::find($sportId);
//            if ($sport) {
//                $user_details = UserDetails::find($userId);
//                $level = UserLevel::find($user_details->level_id);
//
//                $favoritSports[] = [
//                    'sport_id' => $sport->id,
//                    'name' => $sport->name,
//                    'level' => $level ? $level->name : null,
//                    'point'=> 0,
//                ];
//            }
//        }

        $favoritSports = [];

        foreach ($selectedSports as $sportId) {
            $sport = Sport::with(['preferences' => function ($query) {
                $query->with('options');
            }])->find($sportId);

            if ($sport) {
                $user_details = UserDetails::find($userId);
                $level = UserLevel::find($user_details->level_id);

                $favoritSports[] = [
                    'sport_id' => $sport->id,
                    'name' => $sport->name,
                    'level' => $level ? $level->name : null,
                    'point' => 0,
                    'preferences' => $sport->preferences->map(function ($preference) {
                        return [
                            'id' => $preference->id,
                            'name' => $preference->name,
                            'options' => $preference->options->map(function ($option) {
                                return [
                                    'id' => $option->id,
                                    'name' => $option->name,
                                    // يمكنك إضافة المزيد من البيانات هنا إذا أردت
                                ];
                            })
                        ];
                    })
                ];
            }
        }


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


    public function updateUserProfile(Request $request)
    {
        $token = $request->header('Authorization');
        $cleanToken = str_replace('Bearer ', '', $token);

        // فحص صحة رمز التوكن
        $user_token = Token::where('token', $cleanToken)->first();
        if (!isset($user_token)) {
            return response()->json(['message' => 'رمز التوكن غير صحيح'], 400);
        }

        $userId = $user_token->user_id;

        // تحديث البيانات الجديدة للمستخدم
        $user = User::find($userId);

        $image = $request->file('image');
        if (!empty($image)) {

            $binaryImage = file_get_contents($image->getRealPath());

            $imageFolderPath = public_path('images');
            if (!file_exists($imageFolderPath)) {
                mkdir($imageFolderPath, 0777, true);
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $type = $request->input('type');

            if (!in_array($type, $allowedExtensions)) {
                return response()->json([
                    'message' => 'صيغة الصورة غير مدعومة. يرجى تحميل صورة بصيغة مدعومة مثل JPG أو PNG أو GIF.',
                ], 400);
            }

            $fileName = uniqid() . '.' . $type;
            $filePath = $imageFolderPath . '/' . $fileName;
            file_put_contents($filePath, $binaryImage);

            $user->image = 'images/'.$fileName;

        }

        // التحقق من وجود قيمة غير فارغة لاسم المستخدم

        $username = $request->input('username');
        if (!empty($username)) {
            $user->name = $username;
        }

        // التحقق من وجود قيمة غير فارغة للجوال
        $phone = $request->input('phone');
        if (!empty($phone)) {
            $user->phone = $phone;
        }

        // التحقق من وجود قيمة غير فارغة للجنس
        $gender = $request->input('gender');
        if (!empty($gender)) {
            $user->gender = $gender;
        }

        $user->save();

        $selectedSports = $request->input('selected_sports');
//        $deletedSports = $request->input('deleted_sports');
////return $selectedSports;
//
//            foreach ($selectedSports as $sportId) {
//                if ($sportId != null) {
//                    // التحقق مما إذا كانت الرياضة مفضلة بالفعل للمستخدم
//                    $existingFavoriteSport = FavoritSports::where('user_id', $userId)
//                        ->where('sport_id', $sportId)
//                        ->first();
//
//                    if ($existingFavoriteSport) {
//                        continue; // تخطي الرياضة إذا كانت مفضلة بالفعل
//                    }
//
//                    // إنشاء رياضة مفضلة جديدة
//                    $favoriteSport = new FavoritSports();
//                    $favoriteSport->user_id = $userId;
//                    $favoriteSport->sport_id = $sportId;
//                    $favoriteSport->save();
//
//                    $data = new UserDetails();
//
//                    $data->user_id= $userId;
//                    $data->sport_id= $sportId;
//                    $data->level_id= 1;
//
//                    $data->save();
//                }
//
//
//            }
//
//
//
//
//        // حذف الرياضات المفضلة
//        if ($deletedSports) {
//            foreach ($deletedSports as $sportId) {
//                // البحث عن الرياضة المفضلة للحذف
//                $favoriteSport = FavoritSports::where('user_id', $userId)
//                    ->where('sport_id', $sportId)
//                    ->first();
//
//                if ($favoriteSport) {
//                    $favoriteSport->delete();
//                }
//            }
//        }

        // تحقق وإضافة الرياضات
        foreach ($selectedSports as $sportId) {
            if ($sportId != null) {
                $existingFavoriteSport = FavoritSports::where('user_id', $userId)
                    ->where('sport_id', $sportId)
                    ->first();

                if (! $existingFavoriteSport) {
                    $favoriteSport = new FavoritSports();
                    $favoriteSport->user_id = $userId;
                    $favoriteSport->sport_id = $sportId;
                    $favoriteSport->save();

                    $data = new UserDetails();
                    $data->user_id= $userId;
                    $data->sport_id= $sportId;
                    $data->level_id= 1;
                    $data->save();
                }
            }
        }

// حذف الرياضات
        FavoritSports::where('user_id', $userId)
            ->whereNotIn('sport_id', $selectedSports)
            ->delete();

        $userFavorit = FavoritSports::where('user_id', $userId)->with(['use', 'postion', 'time'])->get();

        $favoritSports = [];

        foreach ($userFavorit as $sportId) {
            $sport = Sport::find($sportId->sport_id);

            if ($sport) {
                $user_details = UserDetails::find($userId);
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


//        foreach ($selectedSports as $sportId) {
//            $sport = Sport::with(['preferences' => function ($query) {
//                $query->with('options');
//            }])->find($sportId);
//
//            if ($sport) {
//                $user_details = UserDetails::find($userId);
//                $level = UserLevel::find($user_details->level_id);
//
//                $favoritSports[] = [
//                    'sport_id' => $sport->id,
//                    'name' => $sport->name,
//                    'level' => $level ? $level->name : null,
//                    'point' => 0,
//                    'preferences' => $sport->preferences->map(function ($preference) {
//                        return [
//                            'id' => $preference->id,
//                            'name' => $preference->name,
//                            'options' => $preference->options->map(function ($option) {
//                                return [
//                                    'id' => $option->id,
//                                    'name' => $option->name,
//                                    // يمكنك إضافة المزيد من البيانات هنا إذا أردت
//                                ];
//                            })
//                        ];
//                    })
//                ];
//            }
//        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_verified' => $user->is_verified,
            'phone' => $user->phone,
            'longitude' => $user->longitude,
            'latitude' => $user->latitude,
            'image' => $user->image ? url($user->image) : '', // تحديث هناو
            'created_at'=>$user->created_at,
            'updated_at'=>$user->updated_at
        ];


        $response = [
            'message' => 'تم تحديث بيانات المستخدم بنجاح.',
            'user' => $data,
            'token' => $user_token->token,
            'favorit_sports' => $favoritSports,

        ];
        return response()->json($response, 200);
      //  return response()->json(['message' => 'تم تحديث بيانات المستخدم بنجاح'], 200);
    }

}
