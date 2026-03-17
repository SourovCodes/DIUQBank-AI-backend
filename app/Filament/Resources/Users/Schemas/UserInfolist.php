<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Profile')
                    ->schema([
                        ImageEntry::make('avatar')
                            ->label('Avatar')
                            ->circular()
                            ->hidden(fn (User $record): bool => blank($record->avatar)),
                        TextEntry::make('name'),
                        TextEntry::make('username')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('email'),
                        TextEntry::make('admin_access')
                            ->label('Admin Access')
                            ->state(fn (User $record): string => $record->canAccessPanel(Filament::getPanel('admin')) ? 'Allowed' : 'Denied')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Allowed' ? 'success' : 'gray'),
                        TextEntry::make('email_verified_at')
                            ->dateTime()
                            ->placeholder('Not verified'),
                        TextEntry::make('submissions_count')
                            ->label('Submissions')
                            ->state(fn (User $record): int => $record->submissions()->count())
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('quick_uploads_count')
                            ->label('Quick Uploads')
                            ->state(fn (User $record): int => $record->quickUploads()->count())
                            ->badge()
                            ->color('gray'),
                    ])
                    ->columns(2),
            ]);
    }
}
