<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersRoom extends Model
{
    protected $table = 'UsersRooms';
    protected $primaryKey = 'URO_ID';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'URO_ROO_ID',
        'URO_USR_ID', 
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

    public function room()
    {
        return $this->belongsTo(Room::class, 'URO_ROO_ID', 'ROO_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'URO_USR_ID', 'USR_ID');
    }
}
