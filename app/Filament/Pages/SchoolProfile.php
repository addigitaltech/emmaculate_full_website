<?php

namespace App\Filament\Pages;

use App\Filament\Support\MediaField;
use App\Models\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

/** One simple page for everything about the school itself: details, logo, contact, map, social, colours, mission and pledge. */
class SchoolProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'School profile';
    protected static ?string $navigationLabel = 'School profile';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'School profile';
    protected static string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage school settings')));
    }

    public function mount(): void
    {
        $s = SchoolSettings::query()->orderBy('id')->firstOrFail();
        $social = is_array($s->social_links) ? $s->social_links : [];
        $tokens = is_array($s->theme_tokens) ? $s->theme_tokens : [];
        $stats = [];
        foreach ((is_array($s->verified_stats) ? $s->verified_stats : []) as $label => $value) {
            $stats[] = ['label' => (string) $label, 'value' => (string) $value];
        }

        $this->form->fill([
            'school_name' => $s->school_name, 'short_name' => $s->short_name, 'motto' => $s->motto, 'description' => $s->description,
            'logo_path' => $s->logo_path, 'favicon_path' => $s->favicon_path,
            'address' => $s->address, 'phone_primary' => $s->phone_primary, 'phone_secondary' => $s->phone_secondary,
            'email' => $s->email, 'whatsapp' => $s->whatsapp, 'office_hours' => $s->office_hours, 'gps_location' => $s->gps_location,
            'facebook' => $social['facebook'] ?? null, 'twitter' => $social['twitter'] ?? ($social['x'] ?? null),
            'instagram' => $social['instagram'] ?? null, 'youtube' => $social['youtube'] ?? null,
            'tiktok' => $social['tiktok'] ?? null, 'linkedin' => $social['linkedin'] ?? null,
            'primary' => $tokens['primary'] ?? null, 'secondary' => $tokens['secondary'] ?? null, 'accent' => $tokens['accent'] ?? null,
            'mission' => $s->mission, 'vision' => $s->vision, 'pledge' => $s->pledge, 'anthem' => $s->anthem, 'identity_closing' => $s->identity_closing,
            'stats' => $stats,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Profile')->tabs([
                    Forms\Components\Tabs\Tab::make('School details')->icon('heroicon-o-building-office-2')->schema([
                        Forms\Components\TextInput::make('school_name')->label('School name')->required()->maxLength(160),
                        Forms\Components\TextInput::make('short_name')->label('Short name (used in page titles)')->required()->maxLength(80),
                        Forms\Components\TextInput::make('motto')->label('Motto')->maxLength(180),
                        Forms\Components\Textarea::make('description')->label('Short description of the school')->rows(3)->maxLength(400)->columnSpanFull(),
                        MediaField::path('logo_path', 'School logo'),
                        MediaField::path('favicon_path', 'Small icon in the browser tab (favicon)'),
                    ])->columns(2),
                    Forms\Components\Tabs\Tab::make('Contact & map')->icon('heroicon-o-map-pin')->schema([
                        Forms\Components\TextInput::make('address')->label('Address')->maxLength(255)->columnSpanFull(),
                        Forms\Components\TextInput::make('phone_primary')->label('Main phone')->tel()->maxLength(40),
                        Forms\Components\TextInput::make('phone_secondary')->label('Second phone (optional)')->tel()->maxLength(40),
                        Forms\Components\TextInput::make('email')->label('School email')->email()->maxLength(190),
                        Forms\Components\TextInput::make('whatsapp')->label('WhatsApp number')->tel()->maxLength(40)->helperText('Shows the green WhatsApp chat button. Example: 08012345678'),
                        Forms\Components\TextInput::make('office_hours')->label('Opening hours')->maxLength(160)->columnSpanFull(),
                        Forms\Components\TextInput::make('gps_location')->label('GPS location of the school')
                            ->placeholder('7.4012, 5.7345')
                            ->rule('regex:/^\\s*-?\\d{1,2}(\\.\\d+)?\\s*,\\s*-?\\d{1,3}(\\.\\d+)?\\s*$/')
                            ->validationMessages(['regex' => 'Write it as two numbers separated by a comma, for example 7.4012, 5.7345.'])
                            ->helperText('Open Google Maps on your phone, press and hold on the school, then copy the two numbers shown at the top. They appear as a map on the Contact page.')
                            ->columnSpanFull(),
                    ])->columns(2),
                    Forms\Components\Tabs\Tab::make('Social media')->icon('heroicon-o-share')->schema([
                        Forms\Components\TextInput::make('facebook')->label('Facebook page link')->url()->maxLength(255),
                        Forms\Components\TextInput::make('instagram')->label('Instagram link')->url()->maxLength(255),
                        Forms\Components\TextInput::make('twitter')->label('X (Twitter) link')->url()->maxLength(255),
                        Forms\Components\TextInput::make('youtube')->label('YouTube link')->url()->maxLength(255),
                        Forms\Components\TextInput::make('tiktok')->label('TikTok link')->url()->maxLength(255),
                        Forms\Components\TextInput::make('linkedin')->label('LinkedIn link')->url()->maxLength(255),
                    ])->columns(2),
                    Forms\Components\Tabs\Tab::make('Mission, pledge & anthem')->icon('heroicon-o-flag')->schema([
                        Forms\Components\Textarea::make('mission')->label('Our mission')->rows(3)->columnSpanFull(),
                        Forms\Components\Textarea::make('vision')->label('Our vision')->rows(3)->columnSpanFull(),
                        Forms\Components\Textarea::make('pledge')->label('School pledge')->rows(8)->helperText('One line per row.')->columnSpanFull(),
                        Forms\Components\Textarea::make('anthem')->label('School anthem')->rows(10)->helperText('One line per row.')->columnSpanFull(),
                        Forms\Components\Textarea::make('identity_closing')->label('Closing statement (optional)')->rows(2)->columnSpanFull(),
                    ]),
                    Forms\Components\Tabs\Tab::make('Colours')->icon('heroicon-o-swatch')->schema([
                        Forms\Components\ColorPicker::make('primary')->label('Main colour (dark blue)'),
                        Forms\Components\ColorPicker::make('secondary')->label('Second colour (maroon)'),
                        Forms\Components\ColorPicker::make('accent')->label('Accent colour (gold)'),
                    ])->columns(3),
                    Forms\Components\Tabs\Tab::make('Homepage numbers')->icon('heroicon-o-chart-bar')->schema([
                        Forms\Components\Repeater::make('stats')->label('Numbers shown on the homepage')
                            ->schema([
                                Forms\Components\TextInput::make('label')->label('What it counts')->required()->maxLength(60)->placeholder('Students'),
                                Forms\Components\TextInput::make('value')->label('Number')->required()->maxLength(20)->placeholder('1000+'),
                            ])->columns(2)->defaultItems(0)->addActionLabel('Add a number')->columnSpanFull()
                            ->helperText('Only add figures the school can prove. Leave empty to hide this strip.'),
                    ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Save changes')->submit('save')];
    }

    public function save(): void
    {
        $d = $this->form->getState();
        $s = SchoolSettings::query()->orderBy('id')->firstOrFail();

        $social = array_filter([
            'facebook' => trim((string) ($d['facebook'] ?? '')), 'instagram' => trim((string) ($d['instagram'] ?? '')),
            'twitter' => trim((string) ($d['twitter'] ?? '')), 'youtube' => trim((string) ($d['youtube'] ?? '')),
            'tiktok' => trim((string) ($d['tiktok'] ?? '')), 'linkedin' => trim((string) ($d['linkedin'] ?? '')),
        ], fn ($value) => $value !== '');
        $tokens = array_filter(['primary' => $d['primary'] ?? null, 'secondary' => $d['secondary'] ?? null, 'accent' => $d['accent'] ?? null], fn ($value) => filled($value));
        $stats = [];
        foreach (($d['stats'] ?? []) as $row) {
            if (filled($row['label'] ?? null) && filled($row['value'] ?? null)) {
                $stats[trim((string) $row['label'])] = trim((string) $row['value']);
            }
        }
        $clean = fn ($value) => filled($value) ? trim((string) $value) : null;

        try {
            $s->fill([
                'school_name' => trim((string) $d['school_name']), 'short_name' => trim((string) $d['short_name']),
                'motto' => $clean($d['motto'] ?? null), 'description' => $clean($d['description'] ?? null),
                'logo_path' => $clean($d['logo_path'] ?? null), 'favicon_path' => $clean($d['favicon_path'] ?? null),
                'address' => $clean($d['address'] ?? null), 'phone_primary' => $clean($d['phone_primary'] ?? null), 'phone_secondary' => $clean($d['phone_secondary'] ?? null),
                'email' => $clean($d['email'] ?? null), 'whatsapp' => $clean($d['whatsapp'] ?? null), 'office_hours' => $clean($d['office_hours'] ?? null),
                'gps_location' => filled($d['gps_location'] ?? null) ? preg_replace('/\\s+/', '', (string) $d['gps_location']) : null,
                'social_links' => $social, 'theme_tokens' => $tokens, 'verified_stats' => $stats,
                'mission' => $clean($d['mission'] ?? null), 'vision' => $clean($d['vision'] ?? null), 'pledge' => $clean($d['pledge'] ?? null),
                'anthem' => $clean($d['anthem'] ?? null), 'identity_closing' => $clean($d['identity_closing'] ?? null),
            ])->save();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('Please check the form')->body(collect($exception->errors())->flatten()->first())->persistent()->send();

            return;
        }

        Notification::make()->success()->title('Saved. The website is updated.')->send();
    }
}
