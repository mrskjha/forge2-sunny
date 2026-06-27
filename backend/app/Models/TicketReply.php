<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'body',
    ];

    /**
     * Get the ticket that the reply belongs to.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the user who wrote the reply.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}