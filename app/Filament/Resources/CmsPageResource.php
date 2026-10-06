<?php

namespace App\Filament\Resources;

use App\Domain\Website\Support\SafePublicUrl;
use App\Filament\Resources\CmsPageResource\Pages;
use App\Models\CmsPage;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class CmsPageResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $model = CmsPage::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        $safeUrlRule = fn () => function (string $attribute, $value, $fail): void {
            if (filled($value) && ! SafePublicUrl::allows((string) $value)) {
                $fail('Use an internal path or an HTTPS link without credentials.');
            }
        };
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(180),
            Forms\Components\TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(180),
            Forms\Components\TextInput::make('eyebrow')->maxLength(120),
            Forms\Components\Textarea::make('excerpt')->rows(3)->maxLength(500)->columnSpanFull(),
            Forms\Components\Textarea::make('content')->rows(8)->helperText('Plain text is always escaped. Prefer the structured content blocks below for page sections.')->columnSpanFull(),
            Forms\Components\Repeater::make('content_blocks')->schema([
                Forms\Components\Select::make('type')->options(['section' => 'Section title (starts a new card)', 'heading' => 'Heading', 'paragraph' => 'Paragraph', 'list' => 'List', 'quote' => 'Quote', 'image' => 'Image', 'cta' => 'Call to action'])->required(),
                Forms\Components\Textarea::make('text')->label('Text or list items (one item per line)')->rows(3)->maxLength(5000)->columnSpanFull(),
                Forms\Components\Select::make('media_id')->label('Image asset')->options(fn () => MediaAsset::query()->orderBy('original_name')->pluck('original_name', 'id'))->searchable()->preload(),
                Forms\Components\TextInput::make('alt_text')->maxLength(250),
                Forms\Components\TextInput::make('cta_label')->maxLength(80),
                Forms\Components\TextInput::make('cta_url')->maxLength(2048)->rules([$safeUrlRule]),
            ])->defaultItems(0)->collapsible()->columnSpanFull(),
            Forms\Components\Select::make('featured_media_id')->label('Featured image')->options(fn () => MediaAsset::query()->where('mime_type', 'like', 'image/%')->orderBy('original_name')->pluck('original_name', 'id'))->searchable()->preload(),
            Forms\Components\Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->default('draft')->required(),
            Forms\Components\DateTimePicker::make('published_at')->helperText('A future publication time keeps the page hidden until that time.'),
            Forms\Components\TextInput::make('seo_title')->maxLength(180),
            Forms\Components\Textarea::make('seo_description')->rows(3)->maxLength(320)->columnSpanFull(),
            Forms\Components\TextInput::make('canonical_url')->maxLength(2048)->rules([$safeUrlRule]),
            Forms\Components\Select::make('social_image_id')->label('Social preview image')->options(fn () => MediaAsset::query()->where('mime_type', 'like', 'image/%')->orderBy('original_name')->pluck('original_name', 'id'))->searchable()->preload(),
            Forms\Components\KeyValue::make('metadata')->keyLabel('Key')->valueLabel('Value')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('published_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCmsPages::route('/'), 'create' => Pages\CreateCmsPage::route('/create'), 'edit' => Pages\EditCmsPage::route('/{record}/edit')];
    }
}
