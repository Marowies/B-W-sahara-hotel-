@php
    $margin = $margin ?? false;
    $search = Botble\Hotel\DataTransferObjects\RoomSearchParams::fromRequest(request()->input());
    $roomUrl = Botble\Hotel\Supports\RoomSearchLink::url($room->url, request()->input(), [
        'start_date' => $search->startDate->format(HotelHelper::getDateFormat()),
        'end_date' => $search->endDate->format(HotelHelper::getDateFormat()),
        'adults' => $search->adults, 'children' => $search->children, 'rooms' => $search->rooms,
    ]);
@endphp

<div @class(['single-services shadow-block mb-30', 'ser-m' => !$margin])>
    <div class="services-thumb hover-zoomin wow fadeInUp animated">
        @if ($images = $room->images)
            <a href="{{ $roomUrl }}">
                <img src="{{ RvMedia::getImageUrl(Arr::first(array_filter((array) $images)), 'medium', false, RvMedia::getDefaultImage()) }}" alt="{{ $room->name }}" loading="lazy">
            </a>
        @endif
    </div>
    <div class="services-content">
        @if (HotelHelper::isBookingEnabled())
            <div class="day-book">
                <ul>
                    <li>
                        @php $externalBookingUrl = theme_option('external_booking_url'); @endphp
                        @if ($externalBookingUrl)
                            <a href="{{ $externalBookingUrl }}" target="_blank" class="book-button-custom btn">
                                {{ __('BOOK NOW FOR :price', ['price' => format_price($room->getRoomTotalPrice($startDate->format(HotelHelper::getDateFormat()), $endDate->format(HotelHelper::getDateFormat()), BaseHelper::stringify(request()->integer('rooms', 1))))]) }}
                            </a>
                        @else
                            <form action="{{ route('public.booking') }}" method="POST">
                                @csrf
                                <input type="hidden" name="room_id" value="{{ $room->id }}">
                                <input type="hidden" name="start_date" value="{{ $startDate = $startDate->format(HotelHelper::getDateFormat()) }}">
                                <input type="hidden" name="end_date" value="{{ $endDate = $endDate->format(HotelHelper::getDateFormat()) }}">
                                <input type="hidden" name="adults" value="{{ $adults }}">
                                <input name="children" type="hidden" value="{{ BaseHelper::stringify(request()->integer('children')) ?: 0 }}">
                                <input name="rooms" type="hidden" value="{{ $roomsOfNumber = BaseHelper::stringify(request()->integer('rooms', 1)) }}">
                                <button class="book-button-custom" type="submit" data-animation="fadeInRight" data-delay=".8s">
                                    {{ __('BOOK NOW FOR :price', ['price' => format_price($room->getRoomTotalPrice($startDate, $endDate, $roomsOfNumber))]) }}
                                </button>
                            </form>
                        @endif
                    </li>
                </ul>
            </div>
        @endif
        <h4><a href="{{ $roomUrl }}">{{ $room->name }}</a></h4>
        @if ($description = $room->description)
            <p class="room-item-custom-truncate" title="{{ $description }}">{!! BaseHelper::clean($description) !!}</p>
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
