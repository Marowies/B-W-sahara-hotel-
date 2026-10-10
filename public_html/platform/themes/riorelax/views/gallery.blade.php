@php
    Theme::set('pageTitle',  $gallery->name);

    // The album name is the page H1 (breadcrumb banner); headings typed into the description must not add another.
    $galleryDescription = preg_replace('#<(/?)h1(\s[^>]*)?>#i', '<$1h2$2>', (string) BaseHelper::clean($gallery->description));
    // Media-only descriptions (an image or embed without text) are content; empty markup and spaces are not.
    $hasGalleryDescription = trim(str_replace(['&nbsp;', "\u{00A0}"], ' ', strip_tags($galleryDescription, '<img><iframe><video><audio><picture><svg><object><embed>'))) !== '';
@endphp

@if (function_exists('get_galleries'))
    <div class="container mt-50 mb-50">
        @if ($hasGalleryDescription)
            <div class="custom-gallery-description text-center">{!! $galleryDescription !!}</div>
        @endif
        <div class="row mt-50">
            <article class="post post--single">
                <div class="post__content">
                    <div class="row" id="list-photo">
                        @foreach (gallery_meta_data($gallery) as $image)
                            @if ($image)
                                @php
                                    // A photo description is caption and alt text only, never a link target.
                                    $caption = (string) BaseHelper::clean(Arr::get($image, 'description'));
                                    $altText = trim(html_entity_decode(strip_tags($caption), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: $gallery->name;
                                    $imageUrl = RvMedia::getImageUrl(Arr::get($image, 'img'), 'galleries');
                                    // Without JavaScript the photo opens the image file itself; only http(s) or site-relative URLs are linked.
                                    $fullImageUrl = RvMedia::getImageUrl(Arr::get($image, 'img'));
                                    $fullImageUrl = preg_match('#^(https?:)?//|^/#i', (string) $fullImageUrl) ? $fullImageUrl : null;
                                @endphp
                                <div class="col-12 col-md-4 mt-20" data-src="{{ $imageUrl }}" data-sub-html="{{ $caption }}">
                                    <div class="photo-item">
                                        <div class="thumb">
                                            @if ($fullImageUrl)
                                                <a href="{{ $fullImageUrl }}">
                                                    <img src="{{ $imageUrl }}" alt="{{ $altText }}" loading="lazy">
                                                </a>
                                            @else
                                                <img src="{{ $imageUrl }}" alt="{{ $altText }}" loading="lazy">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </article>
        </div>
    </div>
@endif
