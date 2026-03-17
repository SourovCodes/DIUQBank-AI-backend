<?php

namespace App\Filament\Resources\Submissions\Tables;

use App\Filament\Resources\Submissions\SubmissionResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Submission $record): string => SubmissionResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('Submission')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('uploader.name')
                    ->label('Uploader')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('question_summary')
                    ->label('Question')
                    ->state(fn (Submission $record): string => $record->question?->getSubmissionDisplayLabel() ?? 'Unknown question')
                    ->wrap(),
                TextColumn::make('section')
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('batch')
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('views')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Uploader')
                    ->options(User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, mixed $value): Builder => $query->whereHas('question', fn (Builder $questionQuery): Builder => $questionQuery->where('department_id', $value)),
                    )),
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(Course::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, mixed $value): Builder => $query->whereHas('question', fn (Builder $questionQuery): Builder => $questionQuery->where('course_id', $value)),
                    )),
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, mixed $value): Builder => $query->whereHas('question', fn (Builder $questionQuery): Builder => $questionQuery->where('semester_id', $value)),
                    )),
                SelectFilter::make('exam_type_id')
                    ->label('Exam Type')
                    ->options(ExamType::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, mixed $value): Builder => $query->whereHas('question', fn (Builder $questionQuery): Builder => $questionQuery->where('exam_type_id', $value)),
                    )),
            ])
            ->recordActions([
                Action::make('openPdf')
                    ->label('PDF')
                    ->color('gray')
                    ->url(fn (Submission $record): ?string => $record->getPdfUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (Submission $record): bool => filled($record->getPdfUrl())),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
