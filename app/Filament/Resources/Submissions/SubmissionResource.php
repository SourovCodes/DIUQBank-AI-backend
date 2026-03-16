<?php

namespace App\Filament\Resources\Submissions;

use App\Filament\Resources\Submissions\Pages\CreateSubmission;
use App\Filament\Resources\Submissions\Pages\EditSubmission;
use App\Filament\Resources\Submissions\Pages\ListSubmissions;
use App\Filament\Resources\Submissions\Schemas\SubmissionForm;
use App\Filament\Resources\Submissions\Tables\SubmissionsTable;
use App\Models\Submission;
use App\Services\Pdf\PdfCompressionService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return SubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubmissionsTable::configure($table);
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
            'question.id',
            'section',
            'batch',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "Submission #{$record->id}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Uploader' => $record->uploader?->name,
            'Question' => $record->question?->getSubmissionDisplayLabel(),
            'Section' => $record->section,
            'Batch' => $record->batch,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with([
            'uploader',
            'question.department',
            'question.course',
            'question.semester',
            'question.examType',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutatePdfData(array $data, ?Submission $record = null): array
    {
        $pdfPath = static::normalizePdfPath($data['pdf_path'] ?? $record?->pdf_path);

        $data['pdf_path'] = $pdfPath;

        if (blank($pdfPath)) {
            $data['pdf_size'] = null;
            $data['compressed_pdf_path'] = null;
            $data['compressed_pdf_size'] = null;

            return $data;
        }

        if ($record instanceof Submission && $record->pdf_path !== $pdfPath) {
            $data['compressed_pdf_path'] = null;
            $data['compressed_pdf_size'] = null;
        }

        $data['pdf_size'] = app(PdfCompressionService::class)->storedFileSize($pdfPath)
            ?? $data['pdf_size']
            ?? $record?->pdf_size;

        return $data;
    }

    protected static function normalizePdfPath(mixed $pdfPath): ?string
    {
        if (is_array($pdfPath)) {
            $pdfPath = reset($pdfPath);
        }

        if (! is_string($pdfPath)) {
            return null;
        }

        $pdfPath = trim($pdfPath);

        return $pdfPath !== '' ? $pdfPath : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissions::route('/'),
            'create' => CreateSubmission::route('/create'),
            'edit' => EditSubmission::route('/{record}/edit'),
        ];
    }
}
