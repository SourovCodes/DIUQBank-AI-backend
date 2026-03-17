<?php

namespace App\Filament\Resources\ExamTypes\Tables;

use App\Filament\Resources\ExamTypes\ExamTypeResource;
use App\Filament\Support\GuardedDeleteAction;
use App\Models\ExamType;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExamTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->recordUrl(fn (ExamType $record): string => ExamTypeResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('requires_section')
                    ->label('Section Required')
                    ->boolean(),
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
