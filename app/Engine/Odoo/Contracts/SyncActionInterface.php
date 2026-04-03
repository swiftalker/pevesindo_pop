<?php

namespace App\Engine\Odoo\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SyncActionInterface
{
    public function execute(Model $record);
}
