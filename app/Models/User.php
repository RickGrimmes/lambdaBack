<?php 

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'Users'; 
    protected $primaryKey = 'USR_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'USR_Name',
        'USR_LastName', 
        'USR_Email',
        'USR_Phone',
        'USR_FCM',
        'USR_Password',
        'USR_UserRole',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'USR_Password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'USR_Password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'ROO_USR_ID', 'USR_ID');
    }

    public function routines()
    {
        return $this->hasMany(Routine::class, 'ROU_USR_ID', 'USR_ID');
    }

    public function userrooms()
    {
        return $this->hasMany(UsersRoom::class, 'URO_USR_ID', 'USR_ID');
    }
}
