<?php

namespace App\Filament\Resources;

use App\Domain\Website\Support\SafePublicUrl;
use App\Filament\Resources\HeroSlideResource\Pages;
use App\Models\HeroSlide;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class HeroSlideResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $model = HeroSlide::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';

    public static function form(Form $form): Form
    {
        $safeUrlRule = fn () => function (string $attribute, $value, $fail): void { if (filled($value) && ! SafePublicUrl::allows((string) $value)) $fail('Use an internal path or an HTTPS link without credentials.'); };
        return $form->schema([
            Forms\Components\TextInput::make('eyebrow')->maxLength(100),
            Forms\Components\TextInput::make('heading')->required()->maxLength(180),
            Forms\Components\Textarea::make('body')->rows(4)->maxLength(1000)->columnSpanFull(),
            Forms\Components\Select::make('image_id')->label('Image')->options(fn () => MediaAsset::query()->where('mime_type', 'like', 'image/%')->orderBy('original_name')->pluck('original_name', 'id'))->searchable()->preload()->required(),
            Forms\Components\TextInput::make('image_alt')->maxLength(250)->helperText('Describe the image for screen-reader users.'),
            Forms\Components\TextInput::make('cta_label')->maxLength(80),
            Forms\Components\TextInput::make('cta_url')->maxLength(2048)->rules([$safeUrlRule]),
            Forms\Components\TextInput::make('secondary_cta_label')->maxLength(80),
            Forms\Components\TextInput::make('secondary_cta_url')->maxLength(2048)->rules([$safeUrlRule]),
            Forms\Components\Toggle::make('is_enabled')->default(true),
            Forms\Components\TextInput::make('sort_order')->integer()->minValue(0)->default(0),
            Forms\Components\TextInput::make('seo_title')->maxLength(180),
            Forms\Components\Textarea::make('seo_description')->rows(3)->maxLength(320)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image_path')->disk('public')->square(),
            Tables\Columns\TextColumn::make('eyebrow'),
            Tables\Columns\TextColumn::make('heading')->searchable(),
            Tables\Columns\IconColumn::make('is_enabled')->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->numeric()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }
    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListHeroSlides::route('/'), 'create' => Pages\CreateHeroSlide::route('/create'), 'edit' => Pages\EditHeroSlide::route('/{record}/edit')]; }
}
