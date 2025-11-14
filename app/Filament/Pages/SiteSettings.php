<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\SiteSettings as SiteSettingsModel;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\DB;
use UnitEnum;

final class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public $site_title;

    public $gtm_id;

    public $phone;

    public $email;

    public $social_links = [];

    protected string $view = 'filament.pages.site-settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog';

    protected static ?string $title = 'Global Settings';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 9999;

    public function mount(): void
    {
        $this->site_title = $this->get('site_title');
        $this->gtm_id = $this->get('gtm_id');
        $this->phone = $this->get('phone');
        $this->email = $this->get('email');
        $this->social_links = $this->get('social_links') ?? [];
    }

    public function save(): void
    {
        $this->setSetting('site_title', $this->site_title);
        $this->setSetting('gtm_id', $this->gtm_id);
        $this->setSetting('phone', $this->phone);
        $this->setSetting('email', $this->email);
        $this->setSetting('social_links', $this->social_links);

        // store setting in cache
        cache()->put('site_settings', [
            'site_title' => $this->site_title,
            'gtm_id' => $this->gtm_id,
            'phone' => $this->phone,
            'email' => $this->email,
            'social_links' => $this->social_links,
        ]);

        Notification::make()->title('Settings updated')->success()->send();
    }

    protected function getFormSchema(): array
    {
        return [
            Tabs::make('Settings Tabs')
                ->tabs([
                    Tab::make('Information')
                        ->schema([
                            TextInput::make('site_title')->label('Site Title')->required(),
                            TextInput::make('phone')->label('Phone Number'),
                            TextInput::make('email')->label('Email Address')->email(),
                        ]),

                    Tab::make('Integration')
                        ->schema([
                            TextInput::make('gtm_id')->label('GTM Container ID'),
                        ]),

                    Tab::make('Social')
                        ->schema([
                            Repeater::make('social_links')
                                ->label('Social Links')
                                ->schema([
                                    TextInput::make('platform')->label('Platform')->required(),
                                    TextInput::make('url')->label('URL')->url()->required(),
                                ])
                                ->columns(2)
                                ->default([]),
                        ]),
                ]),
        ];
    }

    protected function getFormModel(): string
    {
        return self::class;
    }

    protected function getFormStatePath(): string
    {
        return '';
    }

    protected function getForm(): Forms\Form
    {
        return $this->form ??= $this->makeForm()
            ->schema($this->getFormSchema());
    }

    private function setSetting(string $key, $value): void
    {
        SiteSettingsModel::updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value)]
        );

        cache()->forget('site_settings'); // Clear cache if you are caching settings
    }

    private function get(string $key)
    {
        $record = DB::table('site_settings')->where('key', $key)->first();

        return json_decode($record->value ?? '', true) ?? $record->value ?? null;
    }
}
