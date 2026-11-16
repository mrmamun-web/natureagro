<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel('admin');
    }

    public function test_active_admin_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'password' => 'StrongPassword123!',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'StrongPassword123!',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'password' => 'StrongPassword123!',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'WrongPassword123!',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_staff_cannot_login_to_admin_panel(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
            'password' => 'StrongPassword123!',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'StrongPassword123!',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_authenticated_admin_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $this->assertAuthenticatedAs($user);

        $this->post('/admin/logout');

        $this->assertGuest();
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }
}
