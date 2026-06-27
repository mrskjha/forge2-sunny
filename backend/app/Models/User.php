<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'org_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }

    /**
     * Get the organization that the user belongs to.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Get the tickets requested by the user.
     */
    public function requestedTickets()
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    /**
     * Get the tickets assigned to the user.
     */
    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    /**
     * Get the ticket replies by the user.
     */
    public function ticketReplies()
    {
        return $this->hasMany(TicketReply::class, 'user_id');
    }
}
