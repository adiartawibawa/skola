@php
    $color = \App\Support\AppSettings::primaryColor();

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
