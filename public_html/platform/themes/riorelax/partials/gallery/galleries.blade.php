@if (isset($galleries) && !$galleries->isEmpty())
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="my-masonry text-center mb-50">
                <div class="button-group filter-button-group">
                    <button class="active" data-filter="*">{{ __('All') }}</button>
                    @foreach ($galleries as $gallery)
                        {{-- Filter by record ID: album names may contain characters (e.g. &) that break CSS selectors. --}}
                        <button data-filter=".gallery-filter-{{ $gallery->getKey() }}">{{ $gallery->name }}</button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="masonry-gallery-huge">
                <div class="grid">
                    <div class="gallery-wrap">
                        @foreach ($galleries as $gallery)
                            <div class="grid-item gallery-filter-{{ $gallery->getKey() }}">
                                <a href="{{ $gallery->url }}">
                                    <figure class="gallery-image">
                                        <img src="{{ RvMedia::getImageUrl($gallery->image, 'medium', false, RvMedia::getDefaultImage()) }}" alt="{{ $gallery->name }}" class="img" />
                                    </figure>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
