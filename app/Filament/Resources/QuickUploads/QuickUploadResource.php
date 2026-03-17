<?php

namespace App\Filament\Resources\QuickUploads;

use App\Enums\QuickUploadStatus;
use App\Filament\Resources\QuickUploads\Pages\EditQuickUpload;
use App\Filament\Resources\QuickUploads\Pages\ListQuickUploads;
use App\Filament\Resources\QuickUploads\Schemas\QuickUploadForm;
use App\Filament\Resources\QuickUploads\Schemas\QuickUploadInfolist;
use App\Filament\Resources\QuickUploads\Tables\QuickUploadsTable;
use App\Models\QuickUpload;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuickUploadResource extends Resource
{
    protected static ?string $model = QuickUpload::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['uploader', 'reviewer']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = QuickUpload::query()
            ->whereIn('status', [
                QuickUploadStatus::Pending,
                QuickUploadStatus::Processing,
                QuickUploadStatus::AiRejected,
                QuickUploadStatus::ManualReviewRequested,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return QuickUploadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuickUploadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuickUploadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuickUploads::route('/'),
            'edit' => EditQuickUpload::route('/{record}/edit'),
        ];
    }
}
