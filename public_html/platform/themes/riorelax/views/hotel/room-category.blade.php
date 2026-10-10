@php
    Theme::set('pageTitle', $category->name);
    [$startDate, $endDate, $adults, $nights, $children, $numberOfRooms] = HotelHelper::getRoomBookingParams();
    $searchValues = [
        'start_date' => $startDate->format(HotelHelper::getDateFormat()), 'end_date' => $endDate->format(HotelHelper::getDateFormat()),
        'adults' => $adults, 'children' => $children, 'rooms' => $numberOfRooms,
    ];
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
                                <a href="{{ Botble\Hotel\Supports\RoomSearchLink::url($room->url, request()->input(), $searchValues) }}">
                                    <img src="{{ RvMedia::getImageUrl(Arr::first(array_filter((array) $images)), 'medium', false, RvMedia::getDefaultImage()) }}" alt="{{ $room->name }}" loading="lazy">
                                </a>
                            @endif
                        </div>
                        <div class="services-content">
                            <h4><a href="{{ Botble\Hotel\Supports\RoomSearchLink::url($room->url, request()->input(), $searchValues) }}">{{ $room->name }}</a></h4>
                            @if ($description = $room->description)
                                <p class="room-item-custom-truncate" title="{{ $description }}">{!! BaseHelper::clean($description) !!}</p>
                            @endif
                            @php $externalBookingUrl = theme_option('external_booking_url'); @endphp
                            @if ($externalBookingUrl)
                                <div class="day-book mt-10">
                                    <a href="{{ $externalBookingUrl }}" target="_blank" class="book-button-custom btn">
                                        {{ __('Book Now') }}
                                    </a>
                                </div>
                            @endif

                            @if ($room->amenities->isNotEmpty())
                                <div class="icon">
                                    <ul class="d-flex justify-content-evenly">
                                        @foreach ($room->amenities->take(6) as $amenity)
                                            @if ($image = $amenity->getMetaData('icon_image', true) )
                                                <li>
                                                    <img src="{{ RvMedia::getImageUrl($image) }}" alt="{{ $amenity->name }}" loading="lazy">
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


