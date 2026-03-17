<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\QuickUploads\QuickUploadResource;
use App\Models\QuickUpload;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class QuickUploadsRelationManager extends RelationManager
{
    protected static string $relationship = 'quickUploads';

    public function form(Schema $schema): Schema
    {
        return QuickUploadResource::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return QuickUploadResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return QuickUploadResource::table($table)
            ->recordUrl(fn (QuickUpload $record): string => QuickUploadResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->url(fn (QuickUpload $record): string => QuickUploadResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
