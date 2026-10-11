<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolEventResource\Pages;
use App\Filament\Resources\SchoolEventResource\RelationManagers;
use App\Models\SchoolEvent;
use Filament\Forms;
use App\Filament\Support\MediaField;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SchoolEventResource extends AuthorizedResource
{
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $navigationLabel = 'Events';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 2;
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $model = SchoolEvent::class;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Event')->schema([
                Forms\Components\TextInput::make('title')->label('Event name')->required()->maxLength(180)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->label('About the event')->rows(5)->columnSpanFull(),
                Forms\Components\DateTimePicker::make('starts_at')->label('Starts')->required(),
                Forms\Components\DateTimePicker::make('ends_at')->label('Ends (optional)'),
                Forms\Components\TextInput::make('location')->label('Where')->maxLength(180),
                Forms\Components\TextInput::make('registration_url')->label('Link for more information (optional)')->maxLength(2048),
                MediaField::image('image_id', 'Picture (optional)'),
            ])->columns(2),
            Forms\Components\Section::make('Publishing')->schema([
                Forms\Components\Select::make('status')->label('Show on the website?')
                    ->options(['published' => 'Published (visible on the website)', 'draft' => 'Unpublished (hidden)'])->default('published')->required()->native(false),
                Forms\Components\Toggle::make('is_featured')->label('Highlight this event'),
                Forms\Components\Toggle::make('send_email')->label('Email everyone about this event')->default(true)
                    ->helperText('Sent once, automatically, to parents, staff, students with an email and newsletter subscribers.'),
                Forms\Components\DateTimePicker::make('published_at')->label('Publish on (optional)')->helperText('Leave empty to publish now.'),
                Forms\Components\DateTimePicker::make('expires_at')->label('Hide after (optional)'),
            ])->columns(2),
            Forms\Components\Section::make('Search engines (optional)')
                ->description('Leave empty and the website will use the title and summary.')
                ->schema([
                    Forms\Components\TextInput::make('seo_title')->label('Title for Google')->maxLength(70),
                    Forms\Components\Textarea::make('seo_description')->label('Description for Google')->rows(2)->maxLength(160),
                ])->collapsed()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image.path')->label('Picture')->disk('public')->square(),
                Tables\Columns\TextColumn::make('title')->label('Event')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('starts_at')->label('Starts')->dateTime('d M Y, g:i A')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'published' ? 'Published' : 'Unpublished')
                    ->color(fn (?string $state) => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('notified_at')->label('Emailed')->boolean()->getStateUsing(fn (SchoolEvent $record) => $record->notified_at !== null),
            ])
            ->defaultSort('starts_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn (SchoolEvent $record) => $record->status === 'published' ? 'Unpublish' : 'Publish')
                    ->icon(fn (SchoolEvent $record) => $record->status === 'published' ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->action(fn (SchoolEvent $record) => $record->update(['status' => $record->status === 'published' ? 'draft' : 'published'])),
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
            'index' => Pages\ListSchoolEvents::route('/'),
            'create' => Pages\CreateSchoolEvent::route('/create'),
            'edit' => Pages\EditSchoolEvent::route('/{record}/edit'),
        ];
    }
}
