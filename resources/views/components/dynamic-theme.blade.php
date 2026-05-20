@php
    /**
     * Resolves primary color name dari GeneralSettings,
     * lalu output CSS yang memetakan --color-primary-* ke
     * --color-{nama}-* milik Tailwind v4 yang sudah dikompilasi.
     *
     * Aman saat DB belum ready (fresh install / before migrate).
     */
    $validColors = [
        'slate',
        'gray',
        'zinc',
        'neutral',
        'stone',
        'red',
        'orange',
        'amber',
        'yellow',
        'lime',
        'green',
        'emerald',
        'teal',
        'cyan',
        'sky',
        'blue',
        'indigo',
        'violet',
        'purple',
        'fuchsia',
        'pink',
        'rose',
    ];

    try {
        $color = app(\App\Settings\GeneralSettings::class)->primary_color ?? 'red';
        $color = in_array($color, $validColors) ? $color : 'red';
    } catch (\Exception) {
        $color = 'red';
    }

    $shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
@endphp

{{-- Hanya inject jika warna berbeda dari default (red) untuk menghindari redefinisi --}}
@if ($color !== 'red')
    <style>
        :root {
            @foreach ($shades as $shade)
                --color-primary-{{ $shade }}: var(--color-{{ $color }}-{{ $shade }});
            @endforeach
        }
    </style>
@endif
