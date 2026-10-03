@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="widget meta-boxes">
                <div class="widget-title">
                    <h4>
                        <span>{{ trans('plugins/hotel::ical.log_details', ['id' => $log->id]) }}</span>
                    </h4>
                </div>
                <div class="widget-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th>{{ trans('core/base::tables.id') }}</th>
                                    <td>{{ $log->id }}</td>
                                </tr>
                                <tr>
                                    <th>{{ trans('plugins/hotel::room.name') }}</th>
                                    <td>{{ $room->name }}</td>
                                </tr>
                                <tr>
                                    <th>{{ trans('plugins/hotel::ical.calendar') }}</th>
                                    <td>{{ $log->calendar ? $log->calendar->name : trans('plugins/hotel::ical.all_calendars') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ trans('core/base::tables.status') }}</th>
                                    <td>
                                        @include('plugins/hotel::ical.partials.status', ['item' => $log])
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ trans('plugins/hotel::ical.message') }}</th>
                                    <td>{{ $log->message }}</td>
                                </tr>
                                <tr>
                                    <th>{{ trans('core/base::tables.created_at') }}</th>
                                    <td>{{ BaseHelper::formatDateTime($log->created_at) }}</td>
                                </tr>
                                @if($log->data)
                                    <tr>
                                        <th>{{ trans('plugins/hotel::ical.data') }}</th>
                                        <td>
                                            <pre>{{ json_encode($log->data, JSON_PRETTY_PRINT) }}</pre>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
