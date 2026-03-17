<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Course;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Semester;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Question information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('department_id')
                            ->label('Department')
                            ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('Select a department')
                            ->afterStateUpdated(fn (Set $set): mixed => $set('course_id', null)),
                        Select::make('course_id')
                            ->label('Course')
                            ->options(fn (Get $get): array => blank($get('department_id'))
                                ? []
                                : Course::query()
                                    ->where('department_id', $get('department_id'))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a course')
                            ->helperText('Courses are limited to the selected department.'),
                        Select::make('semester_id')
                            ->label('Semester')
                            ->options(fn (): array => Semester::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a semester'),
                        Select::make('exam_type_id')
                            ->label('Exam Type')
                            ->options(fn (): array => ExamType::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select an exam type'),
                    ]),
                Section::make('Timestamps')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (?string $operation): bool => $operation === 'edit')
                    ->schema([
                        TextEntry::make('created_at_display')
                            ->label('Created at')
                            ->state(fn (?Question $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        TextEntry::make('updated_at_display')
                            ->label('Updated at')
                            ->state(fn (?Question $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}
