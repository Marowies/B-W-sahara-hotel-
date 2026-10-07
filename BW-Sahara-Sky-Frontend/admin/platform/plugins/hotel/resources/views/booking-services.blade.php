<div class="service-list">
    @foreach($services as $id => $service)
        <div class="form-check mb-2">
            <input
                class="form-check-input"
                type="checkbox"
                name="services[]"
                id="service-{{ $id }}"
                value="{{ $id }}"
                @if(in_array($id, $selectedServices)) checked @endif
            >
            <label class="form-check-label" for="service-{{ $id }}">
                {{ $service['name'] }} -
                <span class="text-primary">
                    {{ format_price($service['price']) }}
                    @if($service['price_type'] == \Botble\Hotel\Enums\ServicePriceTypeEnum::PER_DAY)
                        {{ trans('plugins/hotel::service.form.price_types.per_day') }}
                    @else
                        {{ trans('plugins/hotel::service.form.price_types.once') }}
                    @endif
                </span>
            </label>
        </div>
    @endforeach
</div>
