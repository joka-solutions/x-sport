<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    use HasFactory;
    protected $fillable=[
        'name'
    ];

    public function favoriteSports()
    {
        return $this->belongsToMany(Sport::class, 'sport_id');
    }

    public function favoriteSport()
    {
        return $this->hasMany(FavoritSports::class, 'sport_id');
    }

    public function preferences() {
        return $this->hasMany(SportPreference::class, 'sport_id');
    }
}
