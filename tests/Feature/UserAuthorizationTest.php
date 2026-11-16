<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    public function test_active_admin_can_access_panel(): void
    {
        $user = new User([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $panel = Filament::getPanel('admin');

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_manager_cannot_access_admin_panel(): void
    {
        $user = new User([
            'role' => 'manager',
            'is_active' => true,
        ]);

        $panel = Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_inactive_admin_cannot_access_panel(): void
    {
        $user = new User([
            'role' => 'admin',
            'is_active' => false,
        ]);

        $panel = Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_staff_cannot_access_admin_panel(): void
    {
        $user = new User([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $panel = Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }
}
