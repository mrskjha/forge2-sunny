<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subject',
        'description',
        'status',
        'priority',
        'tags',
        'requester_id',
        'assignee_id',
        'org_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    /**
     * Get the organization that owns the ticket.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Get the requester (user) for the ticket.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the assignee (user) for the ticket.
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the replies for the ticket.
     */
    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id')->orderBy('created_at');
    }

    /**
     * Scope a query to only include tickets for a specific organization.
     */
    public function scopeForOrganization($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }
}