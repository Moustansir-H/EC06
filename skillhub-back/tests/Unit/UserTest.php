<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_user()
    {
        $user = User::factory()->create(['role' => 'FORMATEUR']);

        $this->assertDatabaseHas('users', ['email' => $user->email]);
        $this->assertEquals('FORMATEUR', $user->role);
    }

    public function test_password_cache_dans_json()
    {
        $user = User::factory()->create();
        $this->assertArrayNotHasKey('password', $user->toArray());
    }
}
