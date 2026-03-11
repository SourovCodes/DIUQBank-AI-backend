<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class Register extends \Filament\Auth\Pages\Register
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getUsernameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->autocomplete('username')
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel())
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : $state);
    }
}
