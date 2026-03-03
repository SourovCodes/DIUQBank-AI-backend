<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->placeholder('Enter full name')
                            ->autocomplete('name'),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('user@example.com')
                            ->autocomplete('email'),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verified at')
                            ->seconds(false),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (?string $operation): bool => $operation === 'create')
                            ->rule(Password::defaults())
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Leave blank when editing to keep the current password.')
                            ->autocomplete('new-password'),
                    ]),
                Section::make('Timestamps')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (?string $operation): bool => $operation === 'edit')
                    ->schema([
                        Placeholder::make('created_at_display')
                            ->label('Created at')
                            ->content(fn (?User $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('updated_at_display')
                            ->label('Updated at')
                            ->content(fn (?User $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}
