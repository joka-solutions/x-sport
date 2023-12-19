<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportPreference extends Model
{
    use HasFactory;
    protected $fillable =[
        'sport_id',
        'name'
    ];


    public function options() {
        return $this->hasMany(PreferenceOption::class, 'preference_id');
    }


}
