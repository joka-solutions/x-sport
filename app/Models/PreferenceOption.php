<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreferenceOption extends Model
{
    use HasFactory;
    protected $fillable =[
        'sport_id',
        'preference_id',
        'name'
    ];

    public function preference() {
        return $this->belongsTo(SportPreference::class, 'preference_id');
    }
}
