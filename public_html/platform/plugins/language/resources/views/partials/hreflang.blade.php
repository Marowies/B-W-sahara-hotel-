@php
    $defaultLocale = Language::getDefaultLocale();
    $defaultProperties = Language::getSupportedLocales()[$defaultLocale] ?? [];
    $defaultCode = Language::formatLocaleForHrefLang($defaultProperties['lang_code'] ?? null);
    $defaultUrl = $hreflangUrls[$defaultCode] ?? Language::getLocalizedURL($defaultLocale, url()->current(), [], false);
@endphp
<link
    href="{{ rtrim($defaultUrl, '/') }}"
    hreflang="x-default"
    rel="alternate"
/>

@foreach ($hreflangUrls as $hreflangCode => $url)
    <link
        href="{{ $url }}"
        hreflang="{{ $hreflangCode }}"
        rel="alternate"
    />
@endforeach
