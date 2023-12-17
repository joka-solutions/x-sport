<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */


    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'location',
        'social_type',
        'social_id',
        'longitude',
        'latitude',
        'is_verified',
        'image'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function details(){
        return $this->hasOne(UserDetails::class);
    }

    public function token(){
        return $this->hasOne(Token::class,'user_id');
    }

    public function favoritSports(){
        return $this->hasMany(FavoritSports::class,'user_id');
    }

    public function followers(){
        return $this->hasMany(Follower::class,'user_id');
    }

    public function points(){
        return $this->hasMany(Point::class,'user_id');
    }

    public function wallet(){
        return $this->hasOne(Wallet::class,'user_id');
    }

    public function matches(){
        return $this->hasMany(Wallet::class,'user_id');
    }

    public function getIsVerifiedAttribute($value)
    {
        return $value ? true : false;
    }
}
