<?php

namespace App\Filament\Resources;

use App\Domain\Website\Support\SafePublicUrl;
use App\Filament\Resources\AdmissionsSettingsResource\Pages;
use App\Models\AdmissionsSettings;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class AdmissionsSettingsResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Admission information';
    protected static ?string $navigationGroup = 'Admissions & messages';
    protected static bool $singleton = true;
    protected static ?string $model = AdmissionsSettings::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    public static function form(Form $form): Form
    {
        $safeUrlRule = fn () => function (string $attribute, $value, $fail): void { if (filled($value) && ! SafePublicUrl::allows((string) $value)) $fail('Use an internal path or an HTTPS link without credentials.'); };
        return $form->schema([
            Forms\Components\Select::make('status')->options(['unconfirmed' => 'Unconfirmed', 'closed' => 'Closed', 'coming_soon' => 'Coming soon', 'open' => 'Open for enquiries'])->default('unconfirmed')->required()->helperText('The application intake form is displayed only when set to Open for enquiries.'),
            Forms\Components\Textarea::make('intro')->rows(4)->maxLength(1200)->columnSpanFull(),
            Forms\Components\Textarea::make('eligibility')->rows(3)->maxLength(1200)->columnSpanFull(),
            Forms\Components\TagsInput::make('process_steps')->placeholder('Add a confirmed step')->columnSpanFull(),
            Forms\Components\TagsInput::make('requirements')->placeholder('Add a confirmed requirement')->columnSpanFull(),
            Forms\Components\Repeater::make('important_dates')->schema([
                Forms\Components\TextInput::make('label')->required()->maxLength(100),
                Forms\Components\DatePicker::make('date'),
            ])->defaultItems(0)->columns(2)->columnSpanFull(),
            Forms\Components\Textarea::make('screening_information')->rows(3)->maxLength(1200)->columnSpanFull(),
            Forms\Components\Select::make('image_id')->label('Admissions image')->options(fn () => MediaAsset::query()->where('mime_type', 'like', 'image/%')->orderBy('original_name')->pluck('original_name', 'id'))->searchable()->preload(),
            Forms\Components\TextInput::make('cta_label')->maxLength(80),
            Forms\Components\TextInput::make('cta_url')->maxLength(2048)->rules([$safeUrlRule]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('intro')->limit(70),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListAdmissionsSettings::route('/'), 'edit' => Pages\EditAdmissionsSettings::route('/{record}/edit')]; }
}
