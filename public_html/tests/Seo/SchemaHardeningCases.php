<?php

// Structured data hardening: BreadcrumbList needs two named crumbs (A1), no placeholder article image (A2),
// script-safe FAQ JSON-LD (A3). Pure builders plus source contracts; no CMS records or hotel facts.

use Botble\Faq\FaqItem;
use Botble\Faq\FaqSupport;
use Botble\Theme\Supports\JsonLd;

// A1: BreadcrumbList

test('BreadcrumbList is omitted for a home-only or unnamed trail', function (): void {
    check(JsonLd::breadcrumbList([]) === null, 'Empty trail produced a BreadcrumbList.');
    check(JsonLd::breadcrumbList([['label' => 'Home', 'url' => 'https://hotel.example']]) === null, 'Home-only trail produced a BreadcrumbList.');
    check(JsonLd::breadcrumbList([['label' => 'Home', 'url' => 'https://hotel.example'], ['label' => '  ', 'url' => 'https://hotel.example/x'], ['label' => '<b></b>', 'url' => 'https://hotel.example/y']]) === null,
        'Unnamed crumbs counted towards the two-item minimum.');
});

test('BreadcrumbList keeps visible names and URLs, skips unnamed crumbs and renumbers', function (): void {
    $schema = JsonLd::breadcrumbList([
        ['label' => 'الرئيسية', 'url' => 'https://hotel.example/ar'],
        ['label' => '', 'url' => 'https://hotel.example/ar/broken'],
        ['label' => 'الغرف', 'url' => 'https://hotel.example/ar/rooms'],
        ['label' => 'B&amp;W Sahara Sky 酒店豪华单人房', 'url' => 'https://hotel.example/zh/rooms/deluxe'],
    ]);
    check($schema['@type'] === 'BreadcrumbList' && $schema['@context'] === 'https://schema.org', 'Wrong BreadcrumbList node.');
    check(array_column($schema['itemListElement'], 'position') === [1, 2, 3], 'Positions not renumbered: ' . json_encode(array_column($schema['itemListElement'], 'position')));
    check(array_column($schema['itemListElement'], 'name') === ['الرئيسية', 'الغرف', 'B&W Sahara Sky 酒店豪华单人房'], 'Names changed or not decoded once.');
    check(array_column($schema['itemListElement'], 'item') === ['https://hotel.example/ar', 'https://hotel.example/ar/rooms', 'https://hotel.example/zh/rooms/deluxe'], 'Crumb URLs changed.');
    $noUrl = JsonLd::breadcrumbList([['label' => 'Home', 'url' => 'https://hotel.example'], ['label' => 'Current', 'url' => '']]);
    check(! array_key_exists('item', $noUrl['itemListElement'][1]) && $noUrl['itemListElement'][1]['name'] === 'Current', 'Empty crumb URL emitted as item.');
    $json = JsonLd::encode(JsonLd::breadcrumbList([['label' => 'Home', 'url' => 'https://hotel.example'], ['label' => 'Room</script><script>x', 'url' => 'https://hotel.example/r']]));
    check(! str_contains($json, '</script') && json_decode($json, true) !== null, 'BreadcrumbList JSON can close its script tag.');
});

test('Theme header builds the BreadcrumbList from the visible trail only', function (): void {
    $header = method_source(Botble\Theme\Theme::class, 'header');
    check(str_contains($header, 'JsonLd::breadcrumbList(array_values($this->breadcrumb->getCrumbs()))'), 'Header does not use the visible, de-duplicated trail.');
    check(! str_contains($header, "'@type' => 'BreadcrumbList'"), 'Header still builds its own BreadcrumbList.');
});

// A2: article image

test('Article schema never uses the site placeholder as its image', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/blog/src/Supports/PostSchema.php');
    check(! str_contains($source, 'getDefaultImage'), 'PostSchema still falls back to the default placeholder.');
    check(str_contains($source, "'image' => \$post->image ? ['@type' => 'ImageObject', 'url' => RvMedia::getImageUrl(\$post->image)] : null,"), 'Article image rule changed.');
});

// A3: FAQ schema

$faq = fn (string $question, string $answer) => new FaqItem($question, $answer);

test('FAQ schema decodes questions once, keeps answer HTML and skips incomplete pairs', function () use ($faq): void {
    $schema = FaqSupport::schema([
        $faq('Is there parking at B&amp;W Sahara Sky?', '<p>Yes, <strong>free</strong> parking.</p>'),
        $faq('  ', '<p>Answer without a question</p>'),
        $faq('Question without an answer?', '<p> </p>'),
        $faq('هل يوجد إفطار؟', 'نعم'),
    ]);
    check($schema['@type'] === 'FAQPage' && count($schema['mainEntity']) === 2, 'Incomplete pairs kept: ' . json_encode($schema));
    check($schema['mainEntity'][0]['name'] === 'Is there parking at B&W Sahara Sky?', 'Question not decoded once.');
    check($schema['mainEntity'][0]['acceptedAnswer'] === ['@type' => 'Answer', 'text' => '<p>Yes, <strong>free</strong> parking.</p>'], 'Answer HTML changed.');
    check($schema['mainEntity'][1]['name'] === 'هل يوجد إفطار؟' && $schema['mainEntity'][1]['acceptedAnswer']['text'] === 'نعم', 'Arabic pair changed.');
    check(FaqSupport::schema([]) === null && FaqSupport::schema([$faq('', '')]) === null, 'Empty FAQ produced a FAQPage.');
});

test('FAQ JSON-LD cannot close its script tag and round-trips', function () use ($faq): void {
    $schema = FaqSupport::schema([$faq('Q</script><script>alert(1)</script>?', '<p>A</p></script><script>alert(2)</script>')]);
    $json = JsonLd::encode($schema);
    check(! str_contains($json, '</script') && ! str_contains($json, '<'), 'FAQ JSON can close its script tag: ' . $json);
    check(json_decode($json, true) === $schema, 'FAQ JSON does not round-trip.');
    $source = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/faq/src/FaqSupport.php');
    check(str_contains($source, "writeScript('faq-schema', JsonLd::encode(\$schema)") && ! str_contains($source, 'json_encode($schema, JSON_UNESCAPED_UNICODE)'), 'FAQ schema not written with JsonLd::encode.');
});
