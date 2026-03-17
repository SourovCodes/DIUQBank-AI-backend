<?php

namespace App\Filament\Resources\Semesters\Tables;

use App\Filament\Resources\Semesters\SemesterResource;
use App\Filament\Support\GuardedDeleteAction;
use App\Models\Semester;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SemestersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->recordUrl(fn (Semester $record): string => SemesterResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
            ])
            ->recordActions([
                EditAction::make(),
                GuardedDeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
