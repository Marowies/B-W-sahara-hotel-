<?php

use Botble\Hotel\Supports\HotelSchema;

$unverifiedProperties = ['starRating', 'aggregateRating', 'review', 'geo', 'checkinTime', 'checkoutTime', 'priceRange',
    'amenityFeature', 'offers', 'occupancy', 'bed', 'floorSize', 'numberOfRooms', 'petsAllowed'];

test('Hotel node uses only published CMS contact details', function () use ($unverifiedProperties): void {
    $socialLinks = json_encode([
        [['key' => 'name', 'value' => 'Facebook'], ['key' => 'url', 'value' => 'https://www.facebook.com/example.hotel']],
        [['key' => 'name', 'value' => 'Instagram'], ['key' => 'url', 'value' => 'https://www.instagram.com/']],
        [['key' => 'name', 'value' => 'Bad'], ['key' => 'url', 'value' => 'javascript:alert(1)']],
    ]);
    $schema = HotelSchema::hotel('https://hotel.example', 'https://hotel.example/ar', 'B&W Sahara Sky Hotel', [
        'hotline' => '+20 100 000 0000', 'email' => 'reservations@hotel.example', 'address' => '<p>Giza,  Egypt</p>', 'social_links' => $socialLinks,
    ], 'https://hotel.example/storage/logo.png');
    check($schema['@type'] === 'Hotel' && $schema['@context'] === 'https://schema.org', 'Wrong type/context.');
    check($schema['@id'] === 'https://hotel.example/#hotel' && $schema['url'] === 'https://hotel.example/ar', 'Wrong id/url.');
    check($schema['address'] === 'Giza, Egypt' && $schema['telephone'] === '+20 100 000 0000', 'Contact text not cleaned.');
    check($schema['sameAs'] === ['https://www.facebook.com/example.hotel'], 'sameAs kept a non-profile URL: ' . json_encode($schema['sameAs']));
    check(! array_intersect($unverifiedProperties, array_keys($schema)), 'Unverified hotel property emitted.');
});

test('Riorelax demo contact values and empty fields are never published', function (): void {
    $schema = HotelSchema::hotel('https://hotel.example', 'https://hotel.example', 'B&W Sahara Sky Hotel', [
        'hotline' => '+908 987 877 09', 'email' => 'info@webmail.com', 'address' => '14/A, Riorelax City, NYC', 'social_links' => '[]',
    ]);
    check(array_keys($schema) === ['@context', '@type', '@id', 'name', 'url'], 'Demo or empty values leaked: ' . json_encode($schema));
    check(HotelSchema::hotel('https://hotel.example', 'https://hotel.example', '  ') === null, 'Hotel node emitted without a name.');
});

test('Hotel @id is identical across language homepages', function (): void {
    $ids = array_map(fn ($home) => HotelSchema::hotel('https://hotel.example/', $home, 'Hotel')['@id'],
        ['https://hotel.example', 'https://hotel.example/ar', 'https://hotel.example/zh']);
    check(count(array_unique($ids)) === 1, 'Hotel @id differs per language.');
});

test('HotelRoom node links to the hotel and omits unverified room facts', function () use ($unverifiedProperties): void {
    $schema = HotelSchema::room('https://hotel.example', 'https://hotel.example/zh/rooms/sky-suite', '天空套房',
        '<p>Desert view</p>', ['https://hotel.example/a.jpg', '', 'https://hotel.example/a.jpg']);
    check($schema['@type'] === 'HotelRoom' && $schema['name'] === '天空套房', 'Wrong room node.');
    check($schema['containedInPlace'] === ['@id' => 'https://hotel.example/#hotel'], 'Room not linked to hotel.');
    check($schema['description'] === 'Desert view' && $schema['image'] === ['https://hotel.example/a.jpg'], 'Room text/images not cleaned.');
    check(! array_intersect($unverifiedProperties, array_keys($schema)), 'Unverified room property emitted.');
});

test('JSON-LD cannot close its script tag and keeps Unicode readable', function (): void {
    $json = HotelSchema::toJson(HotelSchema::room('https://hotel.example', 'https://hotel.example/rooms/x', 'Suite</script><script>alert(1)</script>'));
    check(! str_contains($json, '</script>') && ! str_contains($json, '<'), 'Script tag can be closed: ' . $json);
    check(json_decode($json, true)['name'] === 'Suitealert(1)', 'JSON not decodable to the stripped name.');
    check(str_contains(HotelSchema::toJson(['name' => 'جناح']), 'جناح'), 'Unicode escaped.');
});

test('Hotel schema is written for the homepage and published rooms only', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/platform/plugins/hotel/src/Providers/HotelServiceProvider.php');
    check(str_contains($provider, '$object instanceof Room && ! SeoIndexability::isUnpublished($object) => HotelSchema::room('), 'Room schema wiring missing.');
    check(str_contains($provider, '$object instanceof Page && BaseHelper::isHomepage($object->getKey()) => HotelSchema::hotel('), 'Hotel schema wiring missing.');
    check(str_contains($provider, "writeScript('hotel-schema', HotelSchema::toJson(\$schema), attributes: ['type' => 'application/ld+json'])"), 'Schema not written as JSON-LD.');
});

test('Structured data omits the documented demo hotel title and invalid image values', function (): void {
    check(HotelSchema::hotel('https://hotel.example', 'https://hotel.example', 'Hotel Riorelax') === null, 'Demo hotel title emitted.');
    $hotel = HotelSchema::hotel('https://hotel.example', 'https://hotel.example', 'CMS hotel', [], '/relative-logo.png');
    check(! isset($hotel['logo']) && ! isset($hotel['image']), 'Relative schema logo emitted.');
    $room = HotelSchema::room('https://hotel.example', 'https://hotel.example/rooms/x', 'CMS room', null,
        ['', '/relative.jpg', 'javascript:alert(1)', 'https://hotel.example/image.jpg', 'https://hotel.example/image.jpg']);
    check($room['image'] === ['https://hotel.example/image.jpg'], 'Invalid or duplicate schema image emitted.');
});

test('Malformed CMS UTF-8 cannot break JSON-LD serialization', function (): void {
    $json = HotelSchema::toJson(['name' => "CMS\xB1name", 'description' => '</script>']);
    check(is_array(json_decode($json, true)), 'Invalid CMS bytes broke JSON-LD.');
    check(! str_contains($json, '</script>'), 'Script terminator emitted.');
});
