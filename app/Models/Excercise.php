<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Excercise extends Model
{
    protected $table = 'Excercises';
    protected $primaryKey = 'EXC_ID';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'EXC_Title',
        'EXC_Type',
        'EXC_Instructions',
        'EXC_DifficultyLevel',
        'EXC_ROO_ID',

        'EXC_Media1',
        'EXC_Media2',
        'EXC_Media3',
        'EXC_Media4',
        'EXC_URL1',
        'EXC_URL2',
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
        return $this->belongsTo(Room::class, 'EXC_ROO_ID', 'ROO_ID');
    }

    public function routines()
    {
        return $this->hasMany(Routine::class, 'ROU_EXC_ID', 'EXC_ID');
    }
}
