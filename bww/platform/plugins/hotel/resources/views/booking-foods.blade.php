<div class="food-list">
    @foreach($foods as $id => $food)
        <div class="form-check mb-2">
            <input
                class="form-check-input"
                type="checkbox"
                name="foods[]"
                id="food-{{ $id }}"
                value="{{ $id }}"
                @if(in_array($id, $selectedFoods)) checked @endif
            >
            <label class="form-check-label" for="food-{{ $id }}">
                {{ $food['name'] }} -
                <span class="text-primary">
                    {{ format_price($food['price'], $food['currency_id']) }}
                </span>
            </label>
        </div>
    @endforeach
</div>
