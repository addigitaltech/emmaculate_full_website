<?php

namespace App\Filament\Resources;

use App\Domain\Website\Support\SiteSections;
use App\Filament\Resources\CmsSectionResource\Pages;
use App\Models\CmsSection;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class CmsSectionResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $model = CmsSection::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Page sections';
    protected static ?string $modelLabel = 'page section';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('section_key')
                ->label('Where it appears')
                ->options(SiteSections::KEYS)
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('page_id'))
                ->helperText('Each place on the website can have one section.'),
            Forms\Components\TextInput::make('title')->maxLength(180)->helperText('Heading shown above this section (leave blank to use the standard heading).'),
            Forms\Components\Textarea::make('description')->rows(2)->maxLength(500)->columnSpanFull()->helperText('Optional short introduction under the heading.'),
            Forms\Components\Repeater::make('content')
                ->label('Items')
                ->schema([
                    Forms\Components\Select::make('icon')->options(SiteSections::ICONS)->searchable(),
                    Forms\Components\TextInput::make('title')->required()->maxLength(120),
                    Forms\Components\Textarea::make('text')->rows(2)->maxLength(400)->columnSpanFull(),
                    Forms\Components\Select::make('media_id')
                        ->label('Photo (levels only)')
                        ->options(fn () => MediaAsset::query()->where('mime_type', 'like', 'image/%')->orderBy('original_name')->pluck('original_name', 'id'))
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('url')->label('Link (levels only)')->maxLength(2048),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->collapsible()
                ->reorderable()
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_visible')->label('Show on website')->default(true),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('section_key')->label('Where it appears')->formatStateUsing(fn (?string $state) => SiteSections::KEYS[$state] ?? $state)->searchable()->sortable(),
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\IconColumn::make('is_visible')->boolean()->label('Visible'),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCmsSections::route('/'),
            'create' => Pages\CreateCmsSection::route('/create'),
            'edit' => Pages\EditCmsSection::route('/{record}/edit'),
        ];
    }
}
