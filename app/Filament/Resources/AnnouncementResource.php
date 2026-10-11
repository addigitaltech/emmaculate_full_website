<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Filament\Resources\AnnouncementResource\RelationManagers;
use App\Models\Announcement;
use Filament\Forms;
use App\Filament\Support\MediaField;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AnnouncementResource extends AuthorizedResource
{
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $navigationLabel = 'Announcements';
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?int $navigationSort = 3;
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $model = Announcement::class;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Announcement')->schema([
                Forms\Components\TextInput::make('title')->label('Heading')->required()->maxLength(160)->columnSpanFull(),
                Forms\Components\Textarea::make('body')->label('Message')->rows(4)->maxLength(2000)->columnSpanFull(),
                MediaField::image('image_id', 'Picture (optional)'),
                Forms\Components\Select::make('status')->label('Show on the website?')
                    ->options(['published' => 'Published (visible on the website)', 'draft' => 'Unpublished (hidden)'])->default('published')->required()->native(false)
                    ->helperText('Choose Unpublished to hide it without deleting it.'),
            ])->columns(2),
            Forms\Components\Section::make('Button (optional)')->schema([
                Forms\Components\TextInput::make('link_label')->label('Button text')->maxLength(80),
                Forms\Components\TextInput::make('link_url')->label('Button link')->maxLength(2048)->helperText('Use /admissions for a page on this site, or a full https:// address.'),
            ])->columns(2)->collapsed(),
            Forms\Components\Section::make('Show only between these dates (optional)')
                ->description('Leave both empty to show it straight away until you unpublish it.')
                ->schema([
                    Forms\Components\DateTimePicker::make('starts_at')->label('Start showing on'),
                    Forms\Components\DateTimePicker::make('expires_at')->label('Stop showing on'),
                ])->columns(2)->collapsed(),
            Forms\Components\Hidden::make('sort_order')->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image.path')->label('Picture')->disk('public')->square(),
                Tables\Columns\TextColumn::make('title')->label('Heading')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'published' ? 'Published' : 'Unpublished')
                    ->color(fn (?string $state) => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('starts_at')->label('From')->dateTime('d M Y')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('expires_at')->label('Until')->dateTime('d M Y')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn (Announcement $record) => $record->status === 'published' ? 'Unpublish' : 'Publish')
                    ->icon(fn (Announcement $record) => $record->status === 'published' ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->action(fn (Announcement $record) => $record->update(['status' => $record->status === 'published' ? 'draft' : 'published'])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
