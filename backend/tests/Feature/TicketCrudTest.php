<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createOrgWithUsers(string $slug = 'crud-corp'): array
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

    public function test_admin_can_list_tickets(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        Ticket::create([
            'subject' => 'Ticket 1',
            'description' => 'Description 1',
            'status' => 'open',
            'priority' => 'high',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        Ticket::create([
            'subject' => 'Ticket 2',
            'description' => 'Description 2',
            'status' => 'resolved',
            'priority' => 'low',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_customer_can_create_ticket(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tickets', [
                'subject' => 'New Ticket',
                'description' => 'I need help',
                'priority' => 'urgent',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('subject', 'New Ticket')
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('priority', 'urgent')
            ->assertJsonPath('org_id', $org->id)
            ->assertJsonPath('requester_id', $customer->id);
    }

    public function test_create_ticket_validates_fields(): void
    {
        [, , $customer] = $this->createOrgWithUsers();

        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tickets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'description', 'priority']);
    }

    public function test_admin_can_show_ticket(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Show Me',
            'description' => 'Detail here',
            'status' => 'open',
            'priority' => 'medium',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('subject', 'Show Me');
    }

    public function test_admin_can_update_ticket(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Original',
            'description' => 'Original desc',
            'status' => 'open',
            'priority' => 'low',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/tickets/{$ticket->id}", [
                'status' => 'in_progress',
                'priority' => 'high',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'in_progress')
            ->assertJsonPath('priority', 'high');
    }

    public function test_admin_can_delete_ticket(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Delete Me',
            'description' => 'Gone soon',
            'status' => 'open',
            'priority' => 'low',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_customer_cannot_delete_ticket(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Protected',
            'description' => 'Cannot delete',
            'status' => 'open',
            'priority' => 'low',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    public function test_user_can_add_reply_to_ticket(): void
    {
        [$org, $admin, $customer] = $this->createOrgWithUsers();

        $ticket = Ticket::create([
            'subject' => 'Reply Test',
            'description' => 'Need a reply',
            'status' => 'open',
            'priority' => 'medium',
            'requester_id' => $customer->id,
            'org_id' => $org->id,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/tickets/{$ticket->id}/replies", [
                'body' => 'This is a reply.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('body', 'This is a reply.');
    }
}
