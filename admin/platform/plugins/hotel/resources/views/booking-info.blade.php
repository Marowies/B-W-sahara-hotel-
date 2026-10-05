@php
    $route ??= 'invoices.generate';
@endphp

@if ($booking)
    <x-core::datagrid class="mb-4">
        <x-core::datagrid.item :title="trans('plugins/hotel::booking.booking_number')">
            {{ $booking->booking_number }}
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.time')">
            {{ $booking->created_at }}
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.full_name')">
            {{ $booking->address->last_name }}
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.email')">
            <a href="mailto:{{ $booking->address->email }}">{{ $booking->address->email }}</a>
        </x-core::datagrid.item>

        @if ($booking->address->phone)
            <x-core::datagrid.item :title="trans('plugins/hotel::booking.phone')">
                <a href="tel:{{ $booking->address->phone }}">{{ $booking->address->phone }}</a>
            </x-core::datagrid.item>
        @endif

        @if ($booking->address->full_address)
            <x-core::datagrid.item :title="trans('plugins/hotel::booking.address')">
                {{ $booking->address->full_address }}
            </x-core::datagrid.item>
        @endif
    </x-core::datagrid>

    <x-core::datagrid class="mb-4">
        <x-core::datagrid.item :title="trans('plugins/hotel::booking.room')">
            @if ($booking->room->room->exists && ($room = $booking->room->room))
                <a href="{{ $room->url }}" target="_blank">{{ $room->name }}</a>
            @else
                {{ $booking->room->room_name }}
            @endif
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.start_date')">
            {{ $booking->room->start_date }}
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.end_date')">
            {{ $booking->room->end_date }}
        </x-core::datagrid.item>

        @if ($booking->arrival_time)
            <x-core::datagrid.item :title="trans('plugins/hotel::booking.arrival_time')">
                {{ $booking->arrival_time }}
            </x-core::datagrid.item>
        @endif

        @if ($booking->requests)
            <x-core::datagrid.item :title="trans('plugins/hotel::booking.requests')">
                {{ $booking->requests }}
            </x-core::datagrid.item>
        @endif

        @if ($booking->number_of_guests)
            <x-core::datagrid.item :title="trans('plugins/hotel::booking.number_of_guests')">
                {{ $booking->number_of_guests }}
            </x-core::datagrid.item>
        @endif

        @if ($booking->number_of_children)
            <x-core::datagrid.item :title="trans('plugins/hotel::booking.number_of_children')">
                {{ $booking->number_of_children }}
            </x-core::datagrid.item>
        @endif
    </x-core::datagrid>

    <div class="mb-4">
        <h4>{{ trans('plugins/hotel::booking.room') }}</h4>
        <x-core::table>
            <x-core::table.header>
                <x-core::table.header.cell class="text-center" style="width: 150px;">
                    {{ trans('plugins/hotel::booking.image') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell>
                    {{ trans('plugins/hotel::booking.name') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.checkin_date') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.checkout_date') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.number_of_rooms') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.price') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.tax') }}
                </x-core::table.header.cell>
            </x-core::table.header>
            <x-core::table.body>
                <x-core::table.body.row>
                    @if ($booking->room->room->exists && ($room = $booking->room->room))
                        <x-core::table.body.cell
                            class="text-center"
                            style="width: 150px; vertical-align: middle !important;"
                        >
                            <a
                                href="{{ $booking->room->room->url }}"
                                target="_blank"
                            >
                                <img
                                    src="{{ RvMedia::getImageUrl($booking->room->room->image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                                    alt="{{ $booking->room->room_name }}"
                                    width="140"
                                >
                            </a>
                        </x-core::table.body.cell>
                        <x-core::table.body.cell style="vertical-align: middle !important;"><a
                                class="booking-information-link"
                                href="{{ $booking->room->room->url }}"
                                target="_blank"
                            >{{ $booking->room->room->name }}</a></x-core::table.body.cell>
                    @else
                        <x-core::table.body.cell>
                            <img
                                src="{{ RvMedia::getImageUrl($booking->room->room_image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                                alt="{{ $booking->room->room_name }}"
                                width="140"
                            >
                        </x-core::table.body.cell>
                        <x-core::table.body.cell style="vertical-align: middle !important;">{{ $booking->room->room_name }}</x-core::table.body.cell>
                    @endif
                    <x-core::table.body.cell
                        class="text-center"
                        style="vertical-align: middle !important;"
                    >{{ $booking->room->start_date }}</x-core::table.body.cell>
                    <x-core::table.body.cell
                        class="text-center"
                        style="vertical-align: middle !important;"
                    >{{ $booking->room->end_date }}</x-core::table.body.cell>
                    <x-core::table.body.cell
                        class="text-center"
                        style="vertical-align: middle !important;"
                    >{{ $booking->room->number_of_rooms }}</x-core::table.body.cell>
                    <x-core::table.body.cell
                        class="text-center"
                        style="vertical-align: middle !important;"
                    ><strong>{{ format_price($booking->room->price) }}</strong></x-core::table.body.cell>
                    <x-core::table.body.cell
                        class="text-center"
                        style="vertical-align: middle !important;"
                    ><strong>{{ format_price($booking->tax_amount) }}</strong></x-core::table.body.cell>
                </x-core::table.body.row>
            </x-core::table.body>
        </x-core::table>
    </div>

    @if ($booking->services->count())
        <h4>{{ trans('plugins/hotel::booking.services') }}</h4>
        <x-core::table>
            <x-core::table.header>
                <x-core::table.header.cell>
                    {{ trans('plugins/hotel::booking.name') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.price') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.total') }}
                </x-core::table.header.cell>
            </x-core::table.header>
            <x-core::table.body>
                @foreach ($booking->services->unique() as $service)
                    <x-core::table.body.row>
                        <x-core::table.body.cell style="vertical-align: middle !important;">
                            {{ $service->name }}
                        </x-core::table.body.cell>
                        <x-core::table.body.cell class="text-center">
                            {{ format_price($service->price) }} x {{ $booking->room->number_of_rooms }}
                        </x-core::table.body.cell>
                        <x-core::table.body.cell class="text-center">
                            {{ format_price($service->price * $booking->room->number_of_rooms) }}
                        </x-core::table.body.cell>
                    </x-core::table.body.row>
                @endforeach
            </x-core::table.body>
        </x-core::table>
        <br>
    @endif

    @if ($booking->foods->count())
        <h4>{{ trans('plugins/hotel::booking.foods') }}</h4>
        <x-core::table>
            <x-core::table.header>
                <x-core::table.header.cell>
                    {{ trans('plugins/hotel::booking.name') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.price') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell class="text-center">
                    {{ trans('plugins/hotel::booking.total') }}
                </x-core::table.header.cell>
            </x-core::table.header>
            <x-core::table.body>
                @foreach ($booking->foods->unique() as $food)
                    <x-core::table.body.row>
                        <x-core::table.body.cell style="vertical-align: middle !important;">
                            {{ $food->name }}
                        </x-core::table.body.cell>
                        <x-core::table.body.cell class="text-center">
                            {{ format_price($food->price) }}
                        </x-core::table.body.cell>
                        <x-core::table.body.cell class="text-center">
                            {{ format_price($food->price   ) }}
                        </x-core::table.body.cell>
                    </x-core::table.body.row>
                @endforeach
            </x-core::table.body>
        </x-core::table>
        <br>
    @endif

    <x-core::datagrid>
        <x-core::datagrid.item :title="trans('plugins/hotel::booking.sub_total')">
            {{ format_price($booking->sub_total) }}
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.discount_amount')">
            {{ format_price($booking->coupon_amount) }}
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.tax_amount')">
            {{ format_price($booking->tax_amount) }}
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.total_amount')">
            {{ format_price($booking->amount) }}
        </x-core::datagrid.item>

        <x-core::datagrid.item :title="trans('plugins/hotel::booking.status')">
            {!! $booking->status->toHtml() !!}
        </x-core::datagrid.item>

        @if (is_plugin_active('payment') && $booking->payment->id)
            @auth
                <x-core::datagrid.item :title="trans('plugins/hotel::booking.payment_id')">
                    <a href="{{ route('payment.show', $booking->payment->id) }}" target="_blank">
                        {{ $booking->payment->charge_id }}
                        <x-core::icon name="ti ti-external-link" />
                    </a>
                </x-core::datagrid.item>
            @endauth

            <x-core::datagrid.item :title="trans('plugins/hotel::booking.payment_method')">
                {{ $booking->payment->payment_channel->label() }}
            </x-core::datagrid.item>

            <x-core::datagrid.item :title="trans('plugins/hotel::booking.payment_status_label')">
                {!! $booking->payment->status->toHtml() !!}
            </x-core::datagrid.item>

            @if ($booking->payment->payment_channel == \Botble\Payment\Enums\PaymentMethodEnum::BANK_TRANSFER
                && $booking->payment->status == \Botble\Payment\Enums\PaymentStatusEnum::PENDING
            )
                <x-core::datagrid.item :title="trans('plugins/hotel::booking.payment_info')">
                    {!! BaseHelper::clean(get_payment_setting('description', $booking->payment->payment_channel)) !!}
                </x-core::datagrid.item>
            @endif

            @if ($displayBookingStatus ?? false)
                <x-core::datagrid.item :title="trans('plugins/hotel::booking.booking_status')">
                    {!! $booking->status->toHtml() !!}
                </x-core::datagrid.item>
            @endif
        @endif
    </x-core::datagrid>

    @if ((auth()->check() || $booking->customer_id) && ($invoiceId = $booking->invoice->id) && $route)
        <div class="btn-list mt-5">
            <x-core::button
                tag="a"
                :href="route($route, ['invoice' => $invoiceId, 'type' => 'print'])"
                target="_blank"
                icon="ti ti-printer"
                :class="$buttonClass ?? ''"
            >
                {{ trans('plugins/hotel::booking.view_invoice') }}
            </x-core::button>
            <x-core::button
                tag="a"
                :href="route($route, ['invoice' => $invoiceId, 'type' => 'download'])"
                target="_blank"
                icon="ti ti-download"
                :class="$buttonClass ?? ''"
            >
                {{ trans('plugins/hotel::booking.download_invoice') }}
            </x-core::button>
        </div>
    @endif
@endif
