<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Course;
use App\Models\Question;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

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
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set): mixed => $set('course_id', null)),
                        Select::make('course_id')
                            ->label('Course')
                            ->options(fn (Get $get): array => Course::query()
                                ->where('department_id', $get('department_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->disabled(fn (Get $get): bool => blank($get('department_id')))
                            ->rule(fn (Get $get) => Rule::exists('courses', 'id')
                                ->where('department_id', $get('department_id'))),
                        Select::make('semester_id')
                            ->label('Semester')
                            ->relationship('semester', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('exam_type_id')
                            ->label('Exam type')
                            ->relationship('examType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rule(fn (Get $get, ?Question $record) => Rule::unique('questions', 'exam_type_id')
                                ->where('department_id', $get('department_id'))
                                ->where('course_id', $get('course_id'))
                                ->where('semester_id', $get('semester_id'))
                                ->ignore($record)),
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
                            ->content(fn (?Question $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('updated_at_display')
                            ->label('Updated at')
                            ->content(fn (?Question $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}
