<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Routine extends Model
{
    protected $table = 'Routines';
    protected $primaryKey = 'ROU_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ROU_USR_ID',
        'ROU_EXC_ID',
        'ROU_Status',
        'ROU_Fav',
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
        return [
            'ROU_Fav' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'ROU_USR_ID', 'USR_ID');
    }

    public function excercise()
    {
        return $this->belongsTo(Excercise::class, 'ROU_EXC_ID', 'EXC_ID');
    }
}
