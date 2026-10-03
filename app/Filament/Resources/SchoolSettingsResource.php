<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolSettingsResource\Pages;
use App\Models\SchoolSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolSettingsResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage school settings';
    protected static ?string $navigationGroup = 'Website';
    protected static bool $singleton = true;
    protected static ?string $model = SchoolSettings::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('school_name')->required()->maxLength(180),
            Forms\Components\TextInput::make('short_name')->required()->maxLength(80),
            Forms\Components\TextInput::make('motto')->maxLength(180),
            Forms\Components\Textarea::make('description')->rows(4)->maxLength(1200)->columnSpanFull(),
            Forms\Components\FileUpload::make('logo_path')->disk('public')->directory('branding')->visibility('public')->image()->maxSize(2048),
            Forms\Components\FileUpload::make('favicon_path')->disk('public')->directory('branding')->visibility('public')->image()->maxSize(512),
            Forms\Components\TextInput::make('address')->maxLength(255),
            Forms\Components\TextInput::make('phone_primary')->tel()->maxLength(40),
            Forms\Components\TextInput::make('phone_secondary')->tel()->maxLength(40),
            Forms\Components\TextInput::make('email')->email()->maxLength(180),
            Forms\Components\TextInput::make('whatsapp')->maxLength(40),
            Forms\Components\TextInput::make('office_hours')->maxLength(180),
            Forms\Components\KeyValue::make('social_links')->keyLabel('Network')->valueLabel('HTTPS URL')->columnSpanFull(),
            Forms\Components\KeyValue::make('theme_tokens')->keyLabel('Token name')->valueLabel('Value')->columnSpanFull(),
            Forms\Components\KeyValue::make('verified_stats')->keyLabel('Label')->valueLabel('Verified value')->helperText('Only publish figures confirmed by the school.')->columnSpanFull(),
            Forms\Components\Section::make('Results settings')->schema([
                Forms\Components\TextInput::make('ca1_max_score')->numeric()->minValue(0)->maxValue(100)->required()->default(40),
                Forms\Components\TextInput::make('ca2_max_score')->numeric()->minValue(0)->maxValue(100)->required()->default(0),
                Forms\Components\TextInput::make('ca3_max_score')->numeric()->minValue(0)->maxValue(100)->required()->default(0),
                Forms\Components\TextInput::make('exam_max_score')->numeric()->minValue(0)->maxValue(100)->required()->default(60),
                Forms\Components\TextInput::make('pass_percentage')->numeric()->minValue(0)->maxValue(100)->required()->default(40),
                Forms\Components\Toggle::make('highlight_fail_grade')->default(true),
                Forms\Components\Toggle::make('payment_gate_results')->default(false)->helperText('Results are not payment-blocked by default.'),
                Forms\Components\Select::make('result_access_mode')->options(['portal_only' => 'Authenticated student/parent portal'])->default('portal_only')->required(),
            ])->columns(4)->columnSpanFull(),
            Forms\Components\TextInput::make('canonical_base_url')->url()->maxLength(2048),
            Forms\Components\KeyValue::make('global_settings')->keyLabel('Setting')->valueLabel('Value')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('school_name')->searchable(),
            Tables\Columns\TextColumn::make('short_name')->searchable(),
            Tables\Columns\TextColumn::make('motto'),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSchoolSettings::route('/'), 'edit' => Pages\EditSchoolSettings::route('/{record}/edit')];
    }
}
