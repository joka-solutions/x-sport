<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoritSports extends Model
{
    use HasFactory;
    protected $fillable=[
        'user_id',
        'sport_id',
        'point',
        'use_option_id',
        'postion_option_id',
        'time_option_id'
    ];

    public function use() {
        return $this->belongsTo(PreferenceOption::class, 'use_option_id');
    }

    public function postion() {
        return $this->belongsTo(PreferenceOption::class, 'postion_option_id');
    }

    public function time() {
        return $this->belongsTo(PreferenceOption::class, 'time_option_id');
    }


}
