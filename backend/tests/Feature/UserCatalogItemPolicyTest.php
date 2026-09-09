<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserCatalogItemPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeFork(User $owner): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => 'service',
        ]);
    }

    public function test_owner_can_view_own_fork(): void
    {
        $owner = User::factory()->create();
        $fork = $this->makeFork($owner);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $fork));
    }

    public function test_owner_can_update_own_fork(): void
    {
        $owner = User::factory()->create();
        $fork = $this->makeFork($owner);

        $this->assertTrue(Gate::forUser($owner)->allows('update', $fork));
    }

    public function test_owner_can_delete_own_fork(): void
    {
        $owner = User::factory()->create();
        $fork = $this->makeFork($owner);

        $this->assertTrue(Gate::forUser($owner)->allows('delete', $fork));
    }

    public function test_other_user_is_denied_all_abilities(): void
    {
        $owner = User::factory()->create();
        $fork = $this->makeFork($owner);
        $other = User::factory()->create();

        $this->assertFalse(Gate::forUser($other)->allows('view', $fork));
        $this->assertFalse(Gate::forUser($other)->allows('update', $fork));
        $this->assertFalse(Gate::forUser($other)->allows('delete', $fork));
    }

    public function test_admin_is_denied_on_others_fork(): void
    {
        $owner = User::factory()->create();
        $fork = $this->makeFork($owner);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertFalse(Gate::forUser($admin)->allows('view', $fork));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $fork));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $fork));
    }
}
