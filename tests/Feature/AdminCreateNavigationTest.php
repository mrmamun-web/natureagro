<?php

namespace Tests\Feature;

use App\Filament\Resources\FarmTransactions\FarmTransactionResource;
use App\Filament\Resources\FarmTransactions\Pages\ListFarmTransactions;
use App\Filament\Resources\IncubationBatches\IncubationBatchResource;
use App\Filament\Resources\IncubationBatches\Pages\ListIncubationBatches;
use App\Filament\Resources\ParentFlocks\Pages\ListParentFlocks;
use App\Filament\Resources\ParentFlocks\ParentFlockResource;
use App\Filament\Resources\Poultries\Pages\ListPoultries;
use App\Filament\Resources\Poultries\PoultryResource;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Filament\Resources\Sales\SaleResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCreateNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel('admin');
    }

    public function test_new_actions_link_to_their_create_pages(): void
    {
        $pages = [
            [ListPoultries::class, PoultryResource::class, 'New Poultry'],
            [ListParentFlocks::class, ParentFlockResource::class, 'New Parent Flock'],
            [ListIncubationBatches::class, IncubationBatchResource::class, 'New Incubation Batch'],
            [ListSales::class, SaleResource::class, 'New Sale'],
            [ListFarmTransactions::class, FarmTransactionResource::class, 'New Farm Transaction'],
        ];

        foreach ($pages as [$page, $resource, $label]) {
            Livewire::test($page)
                ->assertSee($label)
                ->assertSee('href="'.e($resource::getUrl('create')).'"', false);
        }
    }
}
