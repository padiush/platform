<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationInvite extends Model
{
    use HasFactory;

    public const EXPIRATION_DAYS = 7;

    protected $fillable = [
        'inviting_user_id',
        'invited_name',
        'invited_email',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function invitingUser()
    {
        return $this->belongsTo(User::class, 'inviting_user_id');
    }
}
