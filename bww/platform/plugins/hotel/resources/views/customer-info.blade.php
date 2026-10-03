<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">{{ trans('plugins/hotel::booking.customer_information') }}</h5>
    </div>
    <div class="card-body">
        <div><strong>{{ trans('plugins/hotel::booking.name') }}:</strong> {{ $customer->first_name }} {{ $customer->last_name }}</div>
        <div><strong>Email:</strong> {{ $customer->email }}</div>
        <div><strong>{{ trans('plugins/hotel::booking.phone') }}:</strong> {{ $customer->phone ?? 'N/A' }}</div>
        <div><strong>{{ trans('plugins/hotel::booking.address') }}:</strong> {{ $customer->address ?? 'N/A' }}</div>
    </div>
</div>
