<?php

namespace App\Filament\Resources\ExamTypes;

use App\Filament\Resources\ExamTypes\Pages\CreateExamType;
use App\Filament\Resources\ExamTypes\Pages\EditExamType;
use App\Filament\Resources\ExamTypes\Pages\ListExamTypes;
use App\Filament\Resources\ExamTypes\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\ExamTypes\Schemas\ExamTypeForm;
use App\Filament\Resources\ExamTypes\Tables\ExamTypesTable;
use App\Models\ExamType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamTypeResource extends Resource
{
    protected static ?string $model = ExamType::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Schema $schema): Schema
    {
        return ExamTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExamTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'questions' => QuestionsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Requires section' => $record->requires_section ? 'Yes' : 'No',
            'Questions' => (string) $record->questions_count,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->withCount(['questions']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamTypes::route('/'),
            'create' => CreateExamType::route('/create'),
            'edit' => EditExamType::route('/{record}/edit'),
        ];
    }
}
