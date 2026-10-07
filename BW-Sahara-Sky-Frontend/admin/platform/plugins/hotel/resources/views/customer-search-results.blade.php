@if (count($customers) === 0)
    <div class="dropdown-item">{{ trans('plugins/hotel::booking.no_customers_found') }}</div>
@else
    @foreach ($customers as $customer)
        <a href="javascript:void(0);" class="dropdown-item customer-item" data-id="{{ $customer->id }}">
            <div><strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong></div>
            <div>{{ $customer->email }}</div>
            <div>{{ $customer->phone ?? '' }}</div>
        </a>
    @endforeach
@endif
