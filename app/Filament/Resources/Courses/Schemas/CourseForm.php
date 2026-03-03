<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\Course;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Course information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => request()->query('department_id'))
                            ->required(),
                        TextInput::make('name')
                            ->label('Course name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->rule(fn (callable $get, ?Course $record) => Rule::unique('courses', 'name')
                                ->where('department_id', $get('department_id'))
                                ->ignore($record))
                            ->placeholder('Enter course name'),
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
                            ->content(fn (?Course $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('updated_at_display')
                            ->label('Updated at')
                            ->content(fn (?Course $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}
