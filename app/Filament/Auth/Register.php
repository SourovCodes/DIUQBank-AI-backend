<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Register extends BaseRegister
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
            ->required()
            ->minLength(3)
            ->maxLength(255)
            ->unique($this->getUserModel())
            ->placeholder('Enter username');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $data['name'] = trim((string) $data['name']);
        $data['username'] = trim((string) Str::of((string) $data['username'])->lower());
        $data['email'] = trim((string) Str::of((string) $data['email'])->lower());
        $data['email_verified_at'] = now();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        /** @var Model $user */
        $user = new ($this->getUserModel())();
        $user->forceFill($data);
        $user->save();

        return $user;
    }
}
