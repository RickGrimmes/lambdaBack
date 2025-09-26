<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcerciseMedia extends Model
{
    protected $table = 'ExcerciseMedia';
    protected $primaryKey = 'MED_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'MED_EXC_ID',
        'MED_Media1',
        'MED_Media2',
        'MED_Media3',
        'MED_Media4',
        'MED_URL1',
        'MED_URL2',
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

    public function excercise()
    {
        return $this->belongsTo(Excercise::class, 'MED_EXC_ID', 'EXC_ID');
    }

}
