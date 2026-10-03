<div class="px-3 pb-4">
    @if ($result > 0)
        <span class="text-success">
            {{ trans('plugins/hotel::booking.count_increase', ['count' => number_format($result)]) }}
            <x-core::icon name="ti ti-trending-up" />
        </span>
    @elseif($result < 0)
        <span class="text-danger fw-semibold">
            {{ trans('plugins/hotel::booking.count_decrease', ['count' => number_format($result)]) }}
            <x-core::icon name="ti ti-trending-down" />
        </span>
    @else
        <span class="text-danger fw-semibold" style="visibility: hidden">&mdash;</span>
    @endif
</div>
