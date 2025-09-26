<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'Rooms';
    protected $primaryKey = 'ROO_ID';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ROO_Code',
        'ROO_Name',
        'ROO_USR_ID', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    public function userrooms()
    {
        return $this->hasMany(UsersRoom::class, 'URO_ROO_ID', 'ROO_ID');
    }

    public function excercises()
    {
        return $this->hasMany(Excercise::class, 'EXC_ROO_ID', 'ROO_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'ROO_USR_ID', 'USR_ID');
    }
}
