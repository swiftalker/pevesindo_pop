<?php

arch('engine adapters do not depend on controllers')
    ->expect('App\Engine\Odoo\Adapters')
    ->not->toUse('App\Http\Controllers');

arch('odoo jobs implement ShouldQueue')
    ->expect('App\Jobs\Odoo')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('odoo events use Dispatchable trait')
    ->expect('App\Events\Odoo')
    ->toUseTrait('Illuminate\Foundation\Events\Dispatchable');

arch('engine does not depend on Filament')
    ->expect('App\Engine\Odoo')
    ->not->toUse('Filament');

arch('odoo sync models extend Model')
    ->expect('App\Models\Odoo\Sync')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('listeners live in App\Listeners\Odoo')
    ->expect('App\Listeners\Odoo')
    ->toBeClasses();
