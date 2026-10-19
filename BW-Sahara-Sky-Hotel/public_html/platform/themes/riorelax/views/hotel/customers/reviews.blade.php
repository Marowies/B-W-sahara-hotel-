@extends(HotelHelper::viewPath('customers.master'))

@section('content')
    @php
        SeoHelper::setTitle(__('My Reviews'));
    @endphp

    <header class="sky-page-head">
        <p class="sky-eyebrow">{{ __('My account') }}</p>
        <h1>{{ __('My Reviews') }}</h1>
    </header>

    @if (count($reviews) > 0)
        <ul class="sky-booking-list">
            @foreach ($reviews as $review)
                @php($room = $review->room)
                <li class="sky-card sky-booking-item">
                    <img src="{{ RvMedia::getImageUrl($room->image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="" width="120" height="90" loading="lazy">
                    <div class="sky-booking-body">
                        <h2 class="sky-booking-room"><a href="{{ $room->url }}">{{ $room->name }}</a></h2>
                        <p class="sky-review-text">{{ $review->content }}</p>
                    </div>
                </li>
            @endforeach
        </ul>

        {!! $reviews->links(HotelHelper::viewPath('partials.pagination')) !!}
    @else
        <section class="sky-card sky-empty">
            <h2>{{ __('No reviews yet') }}</h2>
            <p>{{ __('Reviews you write about your stays will appear here.') }}</p>
        </section>
    @endif
@stop
