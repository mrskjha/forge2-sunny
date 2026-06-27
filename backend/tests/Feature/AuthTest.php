<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_org_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'org_name' => 'Test Corp',
            'org_slug' => 'test-corp',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'org_id', 'role'],
                'token',
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Corp',
            'slug' => 'test-corp',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'org_name', 'org_slug']);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $org = Organization::create([
            'name' => 'Login Corp',
            'slug' => 'login-corp',
        ]);

        User::create([
            'name' => 'Login User',
            'email' => 'login@test.com',
            'password' => 'password123',
            'org_id' => $org->id,
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $org = Organization::create([
            'name' => 'Bad Login Corp',
            'slug' => 'bad-login',
        ]);

        User::create([
            'name' => 'User',
            'email' => 'badlogin@test.com',
            'password' => 'password123',
            'org_id' => $org->id,
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'badlogin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_request_works_with_token(): void
    {
        $org = Organization::create([
            'name' => 'Token Corp',
            'slug' => 'token-corp',
        ]);

        $user = User::create([
            'name' => 'Token User',
            'email' => 'token@test.com',
            'password' => 'password123',
            'org_id' => $org->id,
            'role' => 'admin',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_unauthenticated_request_is_blocked(): void
    {
        $response = $this->getJson('/api/tickets');

        $response->assertStatus(401);
    }
}
