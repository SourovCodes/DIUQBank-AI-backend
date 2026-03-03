<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Models\Question;
use App\Models\Submission;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Uploader')
                            ->relationship('uploader', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => request()->query('user_id'))
                            ->required(),
                        Select::make('question_id')
                            ->label('Question')
                            ->relationship(
                                'question',
                                'id',
                                fn (Builder $query): Builder => $query->with([
                                    'department:id,short_name',
                                    'course:id,name',
                                    'semester:id,name',
                                    'examType:id,name',
                                ])
                            )
                            ->getOptionLabelFromRecordUsing(fn (Question $record): string => self::formatQuestionLabel($record))
                            ->getSearchResultsUsing(function (string $search): array {
                                return Question::query()
                                    ->with([
                                        'department:id,short_name',
                                        'course:id,name',
                                        'semester:id,name',
                                        'examType:id,name',
                                    ])
                                    ->where(function (Builder $query) use ($search): void {
                                        $query
                                            ->where('id', 'like', "%{$search}%")
                                            ->orWhereHas('department', fn (Builder $departmentQuery): Builder => $departmentQuery->where('short_name', 'like', "%{$search}%"))
                                            ->orWhereHas('course', fn (Builder $courseQuery): Builder => $courseQuery->where('name', 'like', "%{$search}%"))
                                            ->orWhereHas('semester', fn (Builder $semesterQuery): Builder => $semesterQuery->where('name', 'like', "%{$search}%"))
                                            ->orWhereHas('examType', fn (Builder $examTypeQuery): Builder => $examTypeQuery->where('name', 'like', "%{$search}%"));
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Question $question): array => [$question->id => self::formatQuestionLabel($question)])
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->default(fn () => request()->query('question_id'))
                            ->required(),
                        TextInput::make('section')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('batch')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('views')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),
                        FileUpload::make('pdf_path')
                            ->label('PDF')
                            ->disk('s3')
                            ->directory('submissions')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->openable()
                            ->required(),
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
                            ->content(fn (?Submission $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('updated_at_display')
                            ->label('Updated at')
                            ->content(fn (?Submission $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
                Section::make('PDF preview')
                    ->columnSpanFull()
                    ->visible(fn (?string $operation, ?Submission $record): bool => $operation === 'edit' && filled($record?->pdf_path))
                    ->schema([
                        View::make('filament.resources.submissions.components.pdf-viewer')
                            ->columnSpanFull()
                            ->viewData(function (?Submission $record): array {
                                if (blank($record?->pdf_path)) {
                                    return ['pdfUrl' => null];
                                }

                                /** @var FilesystemAdapter $disk */
                                $disk = Storage::disk('s3');

                                try {
                                    return ['pdfUrl' => $disk->temporaryUrl($record->pdf_path, now()->addMinutes(10))];
                                } catch (Throwable) {
                                    return ['pdfUrl' => $disk->url($record->pdf_path)];
                                }
                            }),
                    ]),
            ]);
    }

    private static function formatQuestionLabel(Question $question): string
    {
        return sprintf(
            '%s - %s | %s | %s | Q#%d',
            $question->department?->short_name ?? 'N/A',
            $question->course?->name ?? 'N/A',
            $question->semester?->name ?? 'N/A',
            $question->examType?->name ?? 'N/A',
            $question->id,
        );
    }
}
