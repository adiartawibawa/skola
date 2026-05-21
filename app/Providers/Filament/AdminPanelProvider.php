<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Settings\GeneralSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $settings = $this->generalSettings();

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->profile(EditProfile::class)
            ->sidebarCollapsibleOnDesktop()
            // ── Identitas dari GeneralSettings ───────────────────────────
            ->brandName($settings?->site_name ?? config('app.name'))
            ->brandLogo($this->resolveLogo($settings?->site_logo))
            ->brandLogoHeight('2rem')
            ->favicon($this->resolveFavicon($settings?->site_favicon))

            // ── Warna dari GeneralSettings ────────────────────────────────
            ->colors([
                'primary' => $this->resolveColor($settings?->primary_color ?? 'red'),
            ])

            // ── Injeksi CSS vars ke <head> Filament ───────────────────────
            // Menyinkronkan --color-primary-* Tailwind dengan tema Filament
            // sehingga komponen custom di dalam panel juga konsisten.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('components.dynamic-theme')->render(),
            )

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Ambil GeneralSettings dari container.
     * Mengembalikan null jika tabel settings belum ada
     * (misal: saat fresh install atau sebelum migrate).
     */
    private function generalSettings(): ?GeneralSettings
    {
        try {
            return app(GeneralSettings::class);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Petakan nama warna (string dari DB) ke Filament Color constant.
     */
    private function resolveColor(string $color): array
    {
        return match ($color) {
            'slate' => Color::Slate,
            'gray' => Color::Gray,
            'zinc' => Color::Zinc,
            'neutral' => Color::Neutral,
            'stone' => Color::Stone,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'amber' => Color::Amber,
            'yellow' => Color::Yellow,
            'lime' => Color::Lime,
            'green' => Color::Green,
            'emerald' => Color::Emerald,
            'teal' => Color::Teal,
            'cyan' => Color::Cyan,
            'sky' => Color::Sky,
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'violet' => Color::Violet,
            'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia,
            'pink' => Color::Pink,
            'rose' => Color::Rose,
            default => Color::Red,
        };
    }

    /**
     * Kembalikan URL logo publik atau null jika belum diset.
     */
    private function resolveLogo(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    /**
     * Kembalikan URL favicon publik atau null jika belum diset.
     */
    private function resolveFavicon(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }
}
