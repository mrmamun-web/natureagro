<?php

namespace App\Filament\Resources\SalePayments\Pages;

use App\Filament\Resources\SalePayments\SalePaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListSalePayments extends ListRecords
{
    protected static string $resource = SalePaymentResource::class;
}
