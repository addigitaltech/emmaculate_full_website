<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsPostResource\Pages;
use App\Filament\Resources\NewsPostResource\RelationManagers;
use App\Models\NewsPost;
use Filament\Forms;
use App\Filament\Support\MediaField;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NewsPostResource extends AuthorizedResource
{
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $navigationLabel = 'News';
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?int $navigationSort = 1;
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $model = NewsPost::class;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('News story')->schema([
                Forms\Components\TextInput::make('title')->label('Headline')->required()->maxLength(180)->columnSpanFull(),
                Forms\Components\Textarea::make('excerpt')->label('Short summary (shown in lists)')->rows(2)->maxLength(300)->columnSpanFull(),
                Forms\Components\Textarea::make('content')->label('Full story')->rows(12)->required()->columnSpanFull()
                    ->helperText('Press Enter twice to start a new paragraph.'),
                MediaField::image('cover_image_id', 'Main picture'),
                Forms\Components\Select::make('category')->label('Type')
                    ->options(['News' => 'News', 'Achievement' => 'Achievement', 'Notice' => 'Notice', 'Events' => 'Events'])->default('News')->native(false),
            ])->columns(2),
            Forms\Components\Section::make('Publishing')->schema([
                Forms\Components\Select::make('status')->label('Show on the website?')
                    ->options(['published' => 'Published (visible on the website)', 'draft' => 'Unpublished (hidden)'])->default('published')->required()->native(false),
                Forms\Components\Toggle::make('is_featured')->label('Highlight this story'),
                Forms\Components\Toggle::make('send_email')->label('Email everyone about this story')->default(true)
                    ->helperText('Sent once, automatically, to parents, staff, students with an email and newsletter subscribers.'),
                Forms\Components\DateTimePicker::make('published_at')->label('Publish on (optional)')->helperText('Leave empty to publish now.'),
                Forms\Components\DateTimePicker::make('expires_at')->label('Hide after (optional)'),
            ])->columns(2),
            Forms\Components\Section::make('More options (optional)')->schema([
                Forms\Components\TextInput::make('subtitle')->label('Sub-heading')->maxLength(180),
                Forms\Components\TagsInput::make('tags')->label('Tags'),
                MediaField::image('social_image_id', 'Picture for sharing on social media'),
            ])->columns(2)->collapsed(),
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
                Tables\Columns\ImageColumn::make('coverImage.path')->label('Picture')->disk('public')->square(),
                Tables\Columns\TextColumn::make('title')->label('Headline')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('category')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'published' ? 'Published' : 'Unpublished')
                    ->color(fn (?string $state) => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('published_at')->label('Published')->dateTime('d M Y')->sortable()->placeholder('—'),
                Tables\Columns\IconColumn::make('notified_at')->label('Emailed')->boolean()->getStateUsing(fn (NewsPost $record) => $record->notified_at !== null),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn (NewsPost $record) => $record->status === 'published' ? 'Unpublish' : 'Publish')
                    ->icon(fn (NewsPost $record) => $record->status === 'published' ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->action(fn (NewsPost $record) => $record->update(['status' => $record->status === 'published' ? 'draft' : 'published'])),
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
            'index' => Pages\ListNewsPosts::route('/'),
            'create' => Pages\CreateNewsPost::route('/create'),
            'edit' => Pages\EditNewsPost::route('/{record}/edit'),
        ];
    }
}
