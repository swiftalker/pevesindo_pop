<?php

namespace App\Enums\Finance\Expenses;

enum PaymentMode: string
{
    case EMPLOYEE = 'employee';
    case COMPANY = 'company';

    public function label(): string
    {
        return match($this) {
            self::EMPLOYEE => 'Employee (Karyawan)',
            self::COMPANY => 'Company (Perusahaan)',
        };
    }
}
