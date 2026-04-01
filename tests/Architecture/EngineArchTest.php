<?php

arch('engine adapters do not depend on controllers')
    ->expect('App\Engine\Odoo\Adapters')
    ->not->toUse('App\Http\Controllers');

arch('engine jobs implement ShouldQueue')
    ->expect('App\Engine\Odoo\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('odoo events use Dispatchable trait')
    ->expect('App\Events\Odoo')
    ->toUseTrait('Illuminate\Foundation\Events\Dispatchable');

arch('engine does not depend on Filament')
    ->expect('App\Engine\Odoo')
    ->not->toUse('Filament');

arch('engine models live in Engine namespace')
    ->expect('App\Engine\Odoo\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('listeners live in App\Listeners\Odoo')
    ->expect('App\Listeners\Odoo')
    ->toBeClasses();
