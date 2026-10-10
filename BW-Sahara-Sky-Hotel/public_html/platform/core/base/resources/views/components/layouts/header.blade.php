<style>
    [v-cloak],
    [x-cloak] {
        display: none;
    }
</style>

{{-- The branded admin uses self-hosted fonts from bw-fonts.css. Avoid a
     synchronous external font download while rendering every admin page. --}}

<style>
    :root {
        --primary-font: "{{ setting('admin_primary_font', 'Inter') }}";
        --primary-color: {{ $primaryColor = setting('admin_primary_color', '#206bc4') }};
        --primary-color-rgb: {{ implode(', ', BaseHelper::hexToRgb($primaryColor)) }};
        --secondary-color: {{ $secondaryColor = setting('admin_secondary_color', '#6c7a91') }};
        --secondary-color-rgb: {{ implode(', ', BaseHelper::hexToRgb($secondaryColor)) }};
        --heading-color: {{ setting('admin_heading_color', 'inherit') }};
        --text-color: {{ $textColor = setting('admin_text_color', '#182433') }};
        --text-color-rgb: {{ implode(', ', BaseHelper::hexToRgb($textColor)) }};
        --link-color: {{ $linkColor = setting('admin_link_color', '#206bc4') }};
        --link-color-rgb: {{ implode(', ', BaseHelper::hexToRgb($linkColor)) }};
        --link-hover-color: {{ $linkHoverColor = setting('admin_link_hover_color', '#206bc4') }};
        --link-hover-color-rgb: {{ implode(', ', BaseHelper::hexToRgb($linkHoverColor)) }};
    }
</style>

{!! Assets::renderHeader(['core']) !!}

<link rel="stylesheet" href="{{ asset('vendor/core/core/base/css/bw-brand/bw-admin.css') }}?v=9">
<script defer src="{{ asset('vendor/core/core/base/js/bw-admin-mobile.js') }}?v=5"></script>
