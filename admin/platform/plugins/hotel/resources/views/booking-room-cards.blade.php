<div class="row mt-3">
    @foreach($rooms as $room)
        <div class="col-md-4 mb-3">
            <div class="card room-card">
                <img src="{{ RvMedia::getImageUrl($room->image) }}" class="card-img-top" alt="{{ $room->name }}">
                <div class="card-body">
                    <h5 class="card-title">{{ $room->name }}</h5>
                    <p class="card-text">
                        <strong>{{ trans('plugins/hotel::booking.price') }}:</strong> {!! $room->price_html !!}
                    </p>
                    <p class="card-text">
                        <strong>{{ trans('plugins/hotel::booking.max_adults') }}:</strong> {{ $room->max_adults }}
                    </p>
                    <p class="card-text">
                        <strong>{{ trans('plugins/hotel::booking.max_children') }}:</strong> {{ $room->max_children }}
                    </p>
                    <button type="button" class="btn btn-primary select-room-button"
                        data-room-id="{{ $room->id }}"
                        data-room-name="{{ $room->name }}"
                        data-room-price="{{ $room->price }}">
                        {{ trans('plugins/hotel::booking.select_room') }}
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>
