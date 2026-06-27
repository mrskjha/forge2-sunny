<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_from_org_a_cannot_see_org_b_tickets(): void
    {
        // Org A
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a']);
        $adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin@orga.com',
            'password' => 'password123',
            'org_id' => $orgA->id,
            'role' => 'admin',
        ]);

        $ticketA = Ticket::create([
            'subject' => 'Org A Ticket',
            'description' => 'Private to Org A',
            'status' => 'open',
            'priority' => 'high',
            'requester_id' => $adminA->id,
            'org_id' => $orgA->id,
        ]);

        // Org B
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b']);
        $adminB = User::create([
            'name' => 'Admin B',
            'email' => 'admin@orgb.com',
            'password' => 'password123',
            'org_id' => $orgB->id,
            'role' => 'admin',
        ]);

        $ticketB = Ticket::create([
            'subject' => 'Org B Ticket',
            'description' => 'Private to Org B',
            'status' => 'open',
            'priority' => 'low',
            'requester_id' => $adminB->id,
            'org_id' => $orgB->id,
        ]);

        // Admin B lists tickets — should only see Org B's ticket
        $tokenB = $adminB->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.subject', 'Org B Ticket')
            ->assertJsonMissing(['subject' => 'Org A Ticket']);
    }

    public function test_user_from_org_a_cannot_access_org_b_ticket_by_id(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a-isolated']);
        $adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin-a@isolated.com',
            'password' => 'password123',
            'org_id' => $orgA->id,
            'role' => 'admin',
        ]);

        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b-isolated']);
        $adminB = User::create([
            'name' => 'Admin B',
            'email' => 'admin-b@isolated.com',
            'password' => 'password123',
            'org_id' => $orgB->id,
            'role' => 'admin',
        ]);

        // Create ticket in Org A
        $ticketA = Ticket::create([
            'subject' => 'Secret Org A Ticket',
            'description' => 'No cross-tenant access',
            'status' => 'open',
            'priority' => 'urgent',
            'requester_id' => $adminA->id,
            'org_id' => $orgA->id,
        ]);

        // Admin B tries to access Org A's ticket by ID — should get 404
        $tokenB = $adminB->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->getJson("/api/tickets/{$ticketA->id}");

        $response->assertStatus(404);
    }

    public function test_user_from_org_a_cannot_update_org_b_ticket(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a-update']);
        $adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin-a@update.com',
            'password' => 'password123',
            'org_id' => $orgA->id,
            'role' => 'admin',
        ]);

        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b-update']);
        $adminB = User::create([
            'name' => 'Admin B',
            'email' => 'admin-b@update.com',
            'password' => 'password123',
            'org_id' => $orgB->id,
            'role' => 'admin',
        ]);

        $ticketA = Ticket::create([
            'subject' => 'Org A Protected',
            'description' => 'Cannot be modified by Org B',
            'status' => 'open',
            'priority' => 'high',
            'requester_id' => $adminA->id,
            'org_id' => $orgA->id,
        ]);

        $tokenB = $adminB->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->putJson("/api/tickets/{$ticketA->id}", [
                'subject' => 'Hacked by Org B',
                'status' => 'closed',
            ]);

        $response->assertStatus(404);

        // Ensure the ticket was NOT modified
        $ticketA->refresh();
        $this->assertEquals('Org A Protected', $ticketA->subject);
        $this->assertEquals('open', $ticketA->status);
    }

    public function test_user_from_org_a_cannot_delete_org_b_ticket(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a-delete']);
        $adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin-a@delete.com',
            'password' => 'password123',
            'org_id' => $orgA->id,
            'role' => 'admin',
        ]);

        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b-delete']);
        $adminB = User::create([
            'name' => 'Admin B',
            'email' => 'admin-b@delete.com',
            'password' => 'password123',
            'org_id' => $orgB->id,
            'role' => 'admin',
        ]);

        $ticketA = Ticket::create([
            'subject' => 'Org A Safe',
            'description' => 'Cannot be deleted by Org B',
            'status' => 'open',
            'priority' => 'high',
            'requester_id' => $adminA->id,
            'org_id' => $orgA->id,
        ]);

        $tokenB = $adminB->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->deleteJson("/api/tickets/{$ticketA->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('tickets', ['id' => $ticketA->id]);
    }

    public function test_user_from_org_a_cannot_reply_to_org_b_ticket(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a-reply']);
        $adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin-a@reply.com',
            'password' => 'password123',
            'org_id' => $orgA->id,
            'role' => 'admin',
        ]);

        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b-reply']);
        $adminB = User::create([
            'name' => 'Admin B',
            'email' => 'admin-b@reply.com',
            'password' => 'password123',
            'org_id' => $orgB->id,
            'role' => 'admin',
        ]);

        $ticketA = Ticket::create([
            'subject' => 'Org A Ticket',
            'description' => 'No cross-tenant replies',
            'status' => 'open',
            'priority' => 'medium',
            'requester_id' => $adminA->id,
            'org_id' => $orgA->id,
        ]);

        $tokenB = $adminB->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->postJson("/api/tickets/{$ticketA->id}/replies", [
                'body' => 'Sneaky reply from Org B',
            ]);

        $response->assertStatus(404);

        $this->assertDatabaseMissing('ticket_replies', ['ticket_id' => $ticketA->id]);
    }
}
