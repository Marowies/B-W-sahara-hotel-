<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1" name="viewport" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://fonts.googleapis.com/css?family={{ urlencode(theme_option('primary_font', 'Epilogue')) }}:400,500,600,700" rel="stylesheet" type="text/css">

    <style>
        :root {
            --primary-color: {{ theme_option('primary_color', '#fec201') }};
            --secondary-color: {{ theme_option('secondary_color', '#034460') }};
            --input-border-color: {{ theme_option('input_border_color', '#d7cfc8') }};
            --primary-color-hover: {{ theme_option('primary_color_hover', '#066a4c') }};
            --btn-text-color-hover: {{ theme_option('button_text_color_hover', '#101010') }};
            --heading-font: '{{ theme_option('heading_font', 'Jost') }}', sans-serif;
            --primary-font: '{{ theme_option('primary_font', 'Roboto') }}', sans-serif;
        }
    </style>
    {!! Theme::header() !!}
    {!! Theme::partial('preloader') !!}
    <style>
        /* =============================================
           BW CUSTOM STYLES — header / hero / booking
           Matches: full-bleed brown header, curved bottom,
           inset rounded hero, floating booking capsule
        ============================================= */

        :root {
            --bw-brown: #4B3621;
            --bw-brown-deep: #3d2b1f;
            --bw-header-curve: 56px;
            --bw-hero-curve: 64px;
            --bw-page-gutter: 28px;
        }

        body { background-color: #ffffff !important; }

        /* ---- HEADER: full-bleed brown, large bottom curves ---- */
        .bw-header {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: var(--bw-brown) !important;
            border-radius: 0 0 var(--bw-header-curve) var(--bw-header-curve) !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            overflow: visible !important;
            box-shadow: 0 8px 28px rgba(45, 28, 18, 0.22) !important;
        }

        /* Kill theme overlay / negative margin that pulls menu into hero */
        .bw-header.header-three .menu-area {
            margin-bottom: 0 !important;
            background: transparent !important;
            position: relative !important;
            z-index: 2 !important;
        }
        .bw-header.header-three .second-header {
            background: transparent !important;
            padding: 10px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        .bw-header .header-top,
        .bw-header .menu-area,
        .bw-header .second-menu {
            background-color: transparent !important;
        }
        .bw-header .second-menu {
            padding: 12px 0 !important;
        }
        .bw-header .main-menu nav > ul > li > a,
        .bw-header .header-cta ul li,
        .bw-header .header-cta ul li span,
        .bw-header .header-cta ul li a,
        .bw-header .header-cta ul li i,
        .bw-header .header-social a,
        .bw-header .header-social span,
        .bw-header .customer-name-header {
            color: #ffffff !important;
        }
        .bw-header .main-menu nav > ul > li > a {
            font-weight: 500 !important;
        }
        .bw-header .main-menu nav > ul > li.active > a,
        .bw-header .main-menu nav > ul > li.current-menu-item > a,
        .bw-header .main-menu nav > ul > li > a:hover {
            font-weight: 700 !important;
            color: #ffffff !important;
        }
        .bw-header .logo img {
            max-height: 48px !important;
            width: auto !important;
        }
        .bw-header .top-btn {
            background: rgba(255,255,255,0.12) !important;
            border: 1px solid rgba(255,255,255,0.25) !important;
            border-radius: 999px !important;
            color: #fff !important;
        }

        /* Theme sticky JS targets #header-sticky (menu only).
           Keep the whole .bw-header sticky via CSS; neutralize fixed jump. */
        .bw-header #header-sticky.sticky-menu,
        .bw-header .menu-area.sticky-menu {
            position: relative !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            width: 100% !important;
            margin: 0 !important;
            background: transparent !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            animation: none !important;
            z-index: 2 !important;
        }
        .bw-header #header-sticky.sticky-menu .main-menu nav > ul > li > a {
            color: #ffffff !important;
        }

        /* When page scrolls, reinforce sticky header look */
        .bw-header.is-stuck,
        .bw-header {
            background-color: var(--bw-brown) !important;
        }

        /* ---- GAP between header curve and hero ---- */
        .bw-gap {
            height: 28px;
            background-color: #ffffff;
            display: block;
            width: 100%;
        }

        /* ---- HERO: inset rounded image plane ---- */
        .bw-hero-wrapper {
            margin: 0 var(--bw-page-gutter) !important;
            position: relative !important;
            padding-bottom: 48px !important; /* room for floating capsule */
        }
        .bw-hero-image-clip {
            border-radius: var(--bw-hero-curve) !important;
            overflow: hidden !important;
            position: relative !important;
            isolation: isolate;
        }
        .bw-hero-image-clip .slider-area {
            margin: 0 !important;
        }
        .bw-hero-image-clip .single-slider {
            min-height: 560px !important;
            background-position: center center !important;
            background-size: cover !important;
            background-repeat: no-repeat !important;
        }
        .bw-hero-image-clip .slider-content.s-slider-content h1, .bw-hero-image-clip .slider-content.s-slider-content h2 {
            font-size: clamp(32px, 4.5vw, 56px) !important;
            line-height: 1.15 !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            text-shadow: 0 2px 16px rgba(0,0,0,0.45) !important;
        }
        .bw-hero-image-clip .slider-content.s-slider-content p {
            color: rgba(255,255,255,0.92) !important;
            text-shadow: 0 1px 8px rgba(0,0,0,0.35) !important;
        }
        .bw-hero-image-clip .slider-btn.mb-105 {
            margin-bottom: 48px !important;
        }
        /* Sibling layout: less bottom padding on wrapper (capsule sits outside) */
        .bw-hero-wrapper:not(:has(> .bw-booking-pill)) {
            padding-bottom: 0 !important;
        }

        /* ---- FLOATING BOOKING CAPSULE ---- */
        /* Inside hero wrapper (hero-banner shortcode) */
        .bw-hero-wrapper > .bw-booking-pill {
            position: absolute !important;
            left: 50% !important;
            bottom: 0 !important;
            transform: translate(-50%, 42%) !important;
            margin: 0 !important;
        }
        /* Sibling after hero (homepage: simple-slider + check-availability) */
        .bw-hero-wrapper + .bw-booking-pill.booking-area.homepage,
        .bw-hero-wrapper + .bw-booking-pill {
            position: relative !important;
            left: auto !important;
            bottom: auto !important;
            transform: none !important;
            margin: -52px auto 56px !important;
        }
        .bw-booking-pill {
            z-index: 30 !important;
            width: min(960px, calc(100% - 56px)) !important;
            padding: 0 !important;
            display: block !important;
        }
        .bw-booking-pill.booking-area.homepage,
        .bw-booking-pill .booking-area.homepage {
            padding: 0 !important;
        }
        .bw-booking-pill .form-booking,
        .bw-booking-pill form.contact-form.form-booking {
            background: #ffffff !important;
            border-radius: 999px !important;
            padding: 18px 18px 18px 36px !important;
            box-shadow: 0 16px 48px rgba(45, 28, 18, 0.18) !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: none !important;
            border: 1px solid rgba(75, 54, 33, 0.06) !important;
        }
        .bw-booking-pill .custom-pill-row,
        .bw-booking-pill .row.custom-pill-row {
            align-items: center !important;
            margin: 0 !important;
            flex-wrap: nowrap !important;
        }
        .bw-booking-pill .mb-30 { margin-bottom: 0 !important; }
        .bw-booking-pill .section-title { display: none !important; }
        .bw-booking-pill .custom-input-pill {
            padding: 0 22px !important;
            border-right: 1px solid #ebe4dc !important;
        }
        .bw-booking-pill .col-lg-3:first-child .custom-input-pill {
            padding-left: 0 !important;
        }
        .bw-booking-pill .col-lg-4 .custom-input-pill {
            border-right: none !important;
        }
        .bw-booking-pill .custom-input-pill label {
            font-weight: 700 !important;
            color: var(--bw-brown) !important;
            font-size: 11px !important;
            display: block !important;
            margin-bottom: 6px !important;
            text-transform: none !important;
            letter-spacing: 0 !important;
            line-height: 1.2 !important;
        }
        .bw-booking-pill .custom-input-pill label i { display: none !important; }
        .bw-booking-pill .custom-input-pill .input-wrapper {
            display: flex !important;
            align-items: center !important;
            background: transparent !important;
            padding: 0 !important;
            gap: 8px;
        }
        .bw-booking-pill .custom-input-pill .input-wrapper input,
        .bw-booking-pill .custom-input-pill .input-wrapper > button {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            color: #6b6560 !important;
            font-size: 14px !important;
            outline: none !important;
            box-shadow: none !important;
            width: 100% !important;
            text-align: left !important;
            line-height: 1.3 !important;
        }
        .bw-booking-pill .custom-input-pill .input-wrapper .input-icon {
            color: #b0a89f !important;
            margin-left: 0 !important;
            font-size: 15px !important;
            flex-shrink: 0 !important;
        }
        .bw-booking-pill .slider-btn {
            margin: 0 !important;
            width: auto !important;
            display: flex !important;
            justify-content: flex-end !important;
            align-items: center !important;
        }
        .bw-booking-pill .round-search-btn,
        .bw-booking-pill .btn.ss-btn.round-search-btn {
            width: 56px !important;
            height: 56px !important;
            border-radius: 50% !important;
            background-color: var(--bw-brown) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            min-width: 56px !important;
            max-width: 56px !important;
            border: none !important;
            float: none !important;
            transition: background-color 0.25s ease, transform 0.25s ease !important;
            box-shadow: 0 6px 18px rgba(75, 54, 33, 0.35) !important;
        }
        .bw-booking-pill .round-search-btn:hover {
            background-color: var(--bw-brown-deep) !important;
            transform: scale(1.06) !important;
            color: #fff !important;
        }
        .bw-booking-pill .round-search-btn i {
            font-size: 18px !important;
            color: #ffffff !important;
            margin: 0 !important;
        }
        .bw-booking-pill .round-search-btn::after,
        .bw-booking-pill .round-search-btn::before { display: none !important; }

        /* Guests dropdown stays usable above capsule */
        .bw-booking-pill .custom-dropdown,
        .bw-booking-pill .dropdown-menu {
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 12px 32px rgba(0,0,0,0.14) !important;
            z-index: 40 !important;
        }

        /* Responsive */
        @media (max-width: 991px) {
            :root {
                --bw-header-curve: 36px;
                --bw-hero-curve: 40px;
                --bw-page-gutter: 16px;
            }
            .bw-gap { height: 18px; }
            .bw-hero-image-clip .single-slider { min-height: 420px !important; }
            .bw-booking-pill,
            .bw-hero-wrapper > .bw-booking-pill,
            .bw-hero-wrapper + .bw-booking-pill {
                position: relative !important;
                left: auto !important;
                bottom: auto !important;
                transform: none !important;
                width: calc(100% - 32px) !important;
                margin: -36px auto 40px !important;
            }
            .bw-hero-wrapper { padding-bottom: 24px !important; }
            .bw-booking-pill .form-booking,
            .bw-booking-pill form.contact-form.form-booking {
                border-radius: 28px !important;
                padding: 22px 20px !important;
            }
            .bw-booking-pill .custom-pill-row,
            .bw-booking-pill .row.custom-pill-row {
                flex-wrap: wrap !important;
            }
            .bw-booking-pill .custom-input-pill {
                border-right: none !important;
                border-bottom: 1px solid #ebe4dc !important;
                padding: 12px 8px !important;
                margin-bottom: 8px !important;
            }
            .bw-booking-pill .col-lg-4 .custom-input-pill { border-bottom: none !important; }
            .bw-booking-pill .slider-btn { justify-content: center !important; margin-top: 8px !important; }
        }

        @media (max-width: 575px) {
            :root {
                --bw-header-curve: 28px;
                --bw-hero-curve: 28px;
                --bw-page-gutter: 12px;
            }
            .bw-hero-image-clip .single-slider { min-height: 360px !important; }
        }
    </style>
</head>
<body @if (BaseHelper::isRtlEnabled()) dir="rtl" @endif>
{!! apply_filters(THEME_FRONT_BODY, null) !!}

@yield('main')

{!! Theme::footer() !!}
@if (session()->has('success_msg') || session()->has('error_msg') || (isset($errors) && $errors->count() > 0) || isset($error_msg))
    <script type="text/javascript">
        $(document).ready(function () {
            @if (session()->has('success_msg'))
                RiorelaxTheme.showSuccess('{{ session('success_msg') }}');
            @endif
            @if (session()->has('error_msg'))
                RiorelaxTheme.showError('{{ session('error_msg') }}');
            @endif
            @if (isset($error_msg))
                RiorelaxTheme.showError('{{ $error_msg }}');
            @endif
            @if (isset($errors))
                @foreach ($errors->all() as $error)
                    RiorelaxTheme.showError('{!! BaseHelper::clean($error) !!}');
                @endforeach
            @endif
        });
    </script>
@endif
</body>
</html>
