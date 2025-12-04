<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'read',
        'read_at'
    ];

    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'USR_ID');
    }

    public function markAsRead()
    {
        $this->update([
            'read' => true,
            'read_at' => now()
        ]);
    }

    // Scopes para consultas
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
