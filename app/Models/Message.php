<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    // what a guest can send from their menu on the public site
    const TYPES = ['feedback' => 'Feedback', 'question' => 'Question'];

    protected $fillable = ['user_id', 'type', 'body', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    // the guest who sent it
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
