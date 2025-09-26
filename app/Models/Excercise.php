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
        'EXC_ROO_ID',
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

    public function media()
    {
        return $this->hasMany(ExcerciseMedia::class, 'MED_EXC_ID', 'EXC_ID');
    }
}
