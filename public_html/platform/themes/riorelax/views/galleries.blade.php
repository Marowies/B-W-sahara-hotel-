@php
    Theme::asset()->container('footer')->usePath()->add('imagesloaded', 'plugins/imagesloaded.min.js', ['jquery']);
    Theme::layout('full-width');
    // Rendered inside the configured Galleries page, whose localized name page.blade.php already set as the H1;
    // the plugin's own listing route (no Galleries page configured) uses its translated label.
    Theme::set('pageTitle', Theme::get('pageTitle') ?: trans('plugins/gallery::gallery.galleries'));
    Theme::set('breadcrumb', true);
@endphp

<section class="section pb-100">
    <section class="profile fix pt-60">
        <div class="container-xxl">
            {!! Theme::partial('gallery.galleries', ['galleries' => $galleries]) !!}
        </div>
    </section>
</section>
