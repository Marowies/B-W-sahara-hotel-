@php(Theme::set('pageTitle', __('Rooms')))

<section class="container">
    <div class="row">
        <div class="col-lg-8">
            {!! Theme::partial('shortcodes.all-rooms.index', compact('rooms', 'startDate', 'endDate', 'nights', 'adults', 'children', 'numberOfRooms')) !!}
        </div>
        <div class="col-lg-4">
            <div class="sidebar-widget-rooms">
                {!! dynamic_sidebar('rooms_sidebar') !!}
            </div>
        </div>
    </div>
</section>
