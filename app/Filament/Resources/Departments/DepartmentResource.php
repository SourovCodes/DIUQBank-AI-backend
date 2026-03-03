<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Resources\Departments\Tables\DepartmentsTable;
use App\Models\Department;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Schema $schema): Schema
    {
        return DepartmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepartmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'short_name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Short name' => $record->short_name,
            'Courses' => (string) $record->courses_count,
            'Questions' => (string) $record->questions_count,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->withCount(['courses', 'questions']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
