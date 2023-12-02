<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matche extends Model
{
    use HasFactory;
    protected $fillable=[
        'match_type_id',
        'sport_id',
        'stadium_id',
        'start_time',
        'user_id',
        'opponent_id',
        'result',
        'evaluation'
    ];

    public function matcheType(){
        return $this->hasOne(MatchType::class,'id','match_type_id');
    }

    public function sport(){
        return $this->hasOne(Sport::class,'id','sport_id');
    }

    public function stadium(){
        return $this->hasOne(Stadium::class,'id','stadium_id');
    }

    public function user(){
        return $this->hasOne(User::class,'id','user_id')->with('details');
    }

    public function opponent(){
        return $this->hasOne(User::class,'id','opponent_id')->with('details');
    }
}
