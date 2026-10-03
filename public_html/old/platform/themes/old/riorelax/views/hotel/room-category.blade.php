@php
    Theme::set('pageTitle', $category->name);
    [$startDate, $endDate, $adults, $nights, $children, $room] = HotelHelper::getRoomBookingParams();
@endphp

<section class="services-area pt-20 pb-40">
    <h3 class="mb-20">{{ __(':count rooms available', ['count' => $rooms->total()]) }}</h3>

    @if ($rooms->isNotEmpty())
        <div class="row">
            @foreach ($rooms as $room)
                <div class="col-md-4">
                    <div class="single-services shadow-block mb-30">
                        <div class="services-thumb hover-zoomin wow fadeInUp animated">
                            @if ($images = $room->images)
                                <a href="{{ $room->url }}?start_date={{ BaseHelper::stringify(request()->query('start_date', $startDate)) }}&end_date={{ BaseHelper::stringify(request()->query('end_date', $endDate)) }}&adults={{ BaseHelper::stringify(request()->query('adults', HotelHelper::getMinimumNumberOfGuests())) }}&children={{ BaseHelper::stringify(request()->query('children', 0)) }}">
                                    <img src="{{ RvMedia::getImageUrl(Arr::first($images), 'medium') }}" alt="{{ $room->name }}">
                                </a>
                            @endif
                        </div>
                        <div class="services-content">
                            <h4><a href="{{ $room->url }}">{{ $room->name }}</a></h4>
                            @if ($description = $room->description)
                                <p class="room-item-custom-truncate" title="{{ $description }}">{!! BaseHelper::clean($description) !!}</p>
                            @endif

                            @if ($room->amenities->isNotEmpty())
                                <div class="icon">
                                    <ul class="d-flex justify-content-evenly">
                                        @foreach ($room->amenities->take(6) as $amenity)
                                            @if ($image = $amenity->getMetaData('icon_image', true) )
                                                <li>
                                                    <img src="{{ RvMedia::getImageUrl($image) }}" alt="{{ $amenity->name }}">
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($rooms instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="text-center mt-30">
                {!! $rooms->withQueryString()->links(Theme::getThemeNamespace('partials.pagination')) !!}
            </div>
        @endif
    @endif
</section>


