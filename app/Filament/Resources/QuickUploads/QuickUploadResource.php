<?php

namespace App\Filament\Resources\QuickUploads;

use App\Enums\QuickUploadStatus;
use App\Filament\Resources\QuickUploads\Pages\EditQuickUpload;
use App\Filament\Resources\QuickUploads\Pages\ListQuickUploads;
use App\Filament\Resources\QuickUploads\Schemas\QuickUploadForm;
use App\Filament\Resources\QuickUploads\Tables\QuickUploadsTable;
use App\Models\QuickUpload;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuickUploadResource extends Resource
{
    protected static ?string $model = QuickUpload::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    public static function form(Schema $schema): Schema
    {
        return QuickUploadForm::configure($schema);
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

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'id',
            'uploader.name',
            'reviewer.name',
            'status',
            'ai_rejection_reason',
            'manual_rejection_reason',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "Quick Upload #{$record->id}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Uploader' => $record->uploader?->name,
            'Reviewer' => $record->reviewer?->name,
            'Status' => $record->status?->label(),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with([
            'uploader',
            'reviewer',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateWorkflowData(array $data): array
    {
        $status = $data['status'] instanceof QuickUploadStatus
            ? $data['status']
            : QuickUploadStatus::from((string) $data['status']);

        $timestamp = now();

        $data['status'] = $status->value;

        if (in_array($status, [
            QuickUploadStatus::AiApproved,
            QuickUploadStatus::AiRejected,
            QuickUploadStatus::ManualReviewRequested,
            QuickUploadStatus::ManualApproved,
            QuickUploadStatus::ManualRejected,
        ], true) && blank($data['ai_processed_at'] ?? null)) {
            $data['ai_processed_at'] = $timestamp;
        }

        if (in_array($status, [QuickUploadStatus::Pending, QuickUploadStatus::Processing], true)) {
            $data['ai_processed_at'] = null;
            $data['ai_rejection_reason'] = null;
        }

        if ($status === QuickUploadStatus::AiApproved) {
            $data['ai_rejection_reason'] = null;
        }

        if (in_array($status, [
            QuickUploadStatus::ManualReviewRequested,
            QuickUploadStatus::ManualApproved,
            QuickUploadStatus::ManualRejected,
        ], true) && blank($data['manual_review_requested_at'] ?? null)) {
            $data['manual_review_requested_at'] = $timestamp;
        }

        if (in_array($status, [QuickUploadStatus::ManualApproved, QuickUploadStatus::ManualRejected], true)) {
            $data['reviewer_id'] = auth()->id() ?? $data['reviewer_id'] ?? null;
            $data['manual_reviewed_at'] = $timestamp;
        } else {
            $data['reviewer_id'] = null;
            $data['manual_reviewed_at'] = null;
        }

        if (! in_array($status, [
            QuickUploadStatus::ManualReviewRequested,
            QuickUploadStatus::ManualApproved,
            QuickUploadStatus::ManualRejected,
        ], true)) {
            $data['manual_review_requested_at'] = null;
        }

        if ($status !== QuickUploadStatus::ManualRejected) {
            $data['manual_rejection_reason'] = null;
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuickUploads::route('/'),
            'edit' => EditQuickUpload::route('/{record}/edit'),
        ];
    }
}
