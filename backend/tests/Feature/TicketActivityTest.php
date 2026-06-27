<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketActivityTest extends TestCase
{
    use RefreshDatabase;

    private function createOrgWithUsers(string $slug = 'activity-corp'): array
    {
        $org = Organization::create([
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => "admin@{$slug}.com",
            'password' => 'password123',
            'org_id' => $org->id,
            'role' => 'admin',
        ]);

        $customer = User::create([
            'name' => 'Customer',
            'email' => "customer@{$slug}.com",
            'password' => 'password123',
            'org_id' => $org->id,
            'role' => 'customer',
        ]);

        return [$org, $admin, $customer];
    }

    public function test_creating_ticket_logs_activity(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tickets', [
                'subject' => 'Activity Test',
                'description' => 'Track me',
                'priority' => 'high',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $response->json('id'),
            'user_id' => $customer->id,
            'action' => 'Ticket created',
        ]);
    }

    public function test_updating_status_logs_activity(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Status Change',
            'description' => 'Change my status',
            'status' => 'open',
            'priority' => 'medium',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/tickets/{$ticket->id}", [
                'status' => 'in_progress',
            ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'action' => 'Changed status to In Progress',
        ]);
    }

    public function test_updating_priority_logs_activity(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Priority Change',
            'description' => 'Change my priority',
            'status' => 'open',
            'priority' => 'low',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/tickets/{$ticket->id}", [
                'priority' => 'urgent',
            ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'action' => 'Changed priority to Urgent',
        ]);
    }

    public function test_replying_logs_activity(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Reply Activity',
            'description' => 'Reply to me',
            'status' => 'open',
            'priority' => 'medium',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/tickets/{$ticket->id}/replies", [
                'body' => 'Here is my reply.',
            ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'action' => 'Replied to ticket',
        ]);
    }

    public function test_show_ticket_includes_activities(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Show Activities',
            'description' => 'Show my activities',
            'status' => 'open',
            'priority' => 'medium',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'action' => 'Ticket created',
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'action' => 'Changed status to In Progress',
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'activities')
            ->assertJsonPath('activities.0.action', 'Changed status to In Progress')
            ->assertJsonPath('activities.1.action', 'Ticket created');
    }

    public function test_new_ticket_has_sla_due_at(): void
    {
        [, , $customer] = $this->createOrgWithUsers();

        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tickets', [
                'subject' => 'SLA Test',
                'description' => 'Check my SLA',
                'priority' => 'high',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('sla_due_at', function ($value) {
                return $value !== null;
            });

        $this->assertNotNull($response->json('sla_due_at'));
    }

    public function test_updating_without_status_change_does_not_log_activity(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'No Change',
            'description' => 'Just update subject',
            'status' => 'open',
            'priority' => 'medium',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/tickets/{$ticket->id}", [
                'subject' => 'Updated Subject',
            ]);

        // No status or priority change → no activity log
        $this->assertDatabaseMissing('ticket_activities', [
            'ticket_id' => $ticket->id,
        ]);
    }
}
