{{-- Optional per-language heading from Theme options → Hotel; the theme's translated label otherwise. --}}
@php(Theme::set('pageTitle', ($pageHeading ?? null) ?: __('Rooms')))

<section class="container">
    <div class="row">
        <div class="col-lg-8">
            {!! do_shortcode('[all-rooms]') !!}
        </div>
        <div class="col-lg-4">
            <div class="sidebar-widget-rooms">
                {!! dynamic_sidebar('rooms_sidebar') !!}
            </div>
        </div>
    </div>
</section>
