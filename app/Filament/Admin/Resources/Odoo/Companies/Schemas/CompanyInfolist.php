<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\Schemas;

// use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Perusahaan')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Section::make([
                            TextEntry::make('name')
                                ->label('Nama Perusahaan'),
                            TextEntry::make('code')
                                ->label('Kode')
                                ->badge(),
                            TextEntry::make('odoo_id')
                                ->label('Odoo ID')
                                ->badge()
                                ->color('gray'),
                        ])->columns(3),
                        Section::make([
                            TextEntry::make('parent.name')
                                ->label('Pusat')
                                ->placeholder('— Ini adalah Pusat —')
                                ->icon('heroicon-o-building-office-2'),
                            TextEntry::make('partner.name')
                                ->label('Kontak Utama')
                                ->placeholder('-')
                                ->icon('heroicon-o-user'),
                            TextEntry::make('currency')
                                ->label('Mata Uang')
                                ->badge()
                                ->color('success'),
                            IconEntry::make('is_active')
                                ->label('Aktif')
                                ->boolean(),
                        ])->columns(4),
                    ]),
                Section::make('Statistik')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Section::make([
                            TextEntry::make('journals_count')
                                ->label('Jurnal')
                                ->state(fn ($record) => $record->journals()->count()),
                            TextEntry::make('bank_accounts_count')
                                ->label('Rekening Bank')
                                ->state(fn ($record) => $record->bankAccounts()->count()),
                            TextEntry::make('analytic_accounts_count')
                                ->label('Akun Analitik')
                                ->state(fn ($record) => $record->analyticAccounts()->count()),
                            TextEntry::make('contacts_count')
                                ->label('Kontak')
                                ->state(fn ($record) => $record->contacts()->count()),
                            TextEntry::make('children_count')
                                ->label('Cabang')
                                ->state(fn ($record) => $record->children()->count()),
                        ])->columns(5),
                    ]),
                Section::make('Timestamp')
                    ->schema([
                        Section::make([
                            TextEntry::make('created_at')
                                ->label('Dibuat')
                                ->dateTime(),
                            TextEntry::make('updated_at')
                                ->label('Diperbarui')
                                ->dateTime(),
                        ])->columns(2),
                    ])
                    ->collapsed(),
            ]);
    }
}
