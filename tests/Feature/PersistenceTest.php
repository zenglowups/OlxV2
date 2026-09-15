<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_and_secure_user_storage_work_on_postgresql(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $user = User::factory()->create(['password' => 'local-test-password']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
        $this->assertTrue(Hash::check('local-test-password', $user->fresh()->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function test_email_uniqueness_is_enforced_by_the_database(): void
    {
        User::factory()->create(['email' => 'unique@example.test']);
        $this->expectException(UniqueConstraintViolationException::class);
        User::factory()->create(['email' => 'unique@example.test']);
    }
}
