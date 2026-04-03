<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\Pages;

use App\Filament\Admin\Resources\Odoo\Companies\CompanyResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;
}
