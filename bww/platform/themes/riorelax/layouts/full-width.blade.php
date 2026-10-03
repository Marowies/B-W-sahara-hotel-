@extends(Theme::getThemeNamespace('layouts.base'))

@section('main')
    {{-- Full-bleed brown header with large bottom curves (matches design) --}}
    <header class="header-area header-three bw-header">
        @if (theme_option('header_top_enabled', true))
            {!! Theme::partial('header-top') !!}
        @endif

        {!! Theme::partial('header') !!}
    </header>

    @if (Theme::get('breadcrumb', true))
        {!! Theme::partial('breadcrumbs') !!}
    @endif

    {{-- Breathable gap between curved header and hero --}}
    <div class="bw-gap" aria-hidden="true"></div>

    {!! Theme::content() !!}

    {!! Theme::partial('footer') !!}
@endsection
