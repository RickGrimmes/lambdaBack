<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    protected $primaryKey = 'NOT_ID';
    
    protected $fillable = [
        'NOT_USR_ID',
        'NOT_Title',
        'NOT_Body',
        'NOT_ROO_ID',
        'NOT_Status'
    ];

    protected $casts = [
        'NOT_Status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'NOT_USR_ID', 'USR_ID');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'NOT_ROO_ID', 'ROO_ID');
    }

    public function scopeUnread($query)
    {
        return $query->where('NOT_Status', 'unread');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('NOT_USR_ID', $userId);
    }
}