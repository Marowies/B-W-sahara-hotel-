<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('plugins/hotel::ical.email.subject') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border: 1px solid #e4e4e4; border-radius: 5px; padding: 20px;">
        <h1 style="color: #e74c3c; margin-top: 0;">{{ trans('plugins/hotel::ical.email.subject') }}</h1>
        
        <p>{{ trans('plugins/hotel::ical.email.greeting') }}</p>
        
        <p>{{ trans('plugins/hotel::ical.email.message') }}</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #e74c3c; margin: 20px 0;">
            <h3 style="margin-top: 0;">{{ trans('plugins/hotel::ical.email.details') }}</h3>
            
            <p><strong>{{ trans('plugins/hotel::ical.email.time') }}:</strong> {{ $data['time'] }}</p>
            <p><strong>{{ trans('plugins/hotel::room.name') }}:</strong> {{ $data['room_name'] }}</p>
            
            @if(isset($data['calendar_name']))
                <p><strong>{{ trans('plugins/hotel::ical.calendar') }}:</strong> {{ $data['calendar_name'] }}</p>
            @endif
            
            <p><strong>{{ trans('plugins/hotel::ical.email.error') }}:</strong> {{ $data['error'] }}</p>
        </div>
        
        <p>{{ trans('plugins/hotel::ical.email.action') }}</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $data['logs_url'] }}" style="background: #3498db; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                {{ trans('plugins/hotel::ical.email.view_logs') }}
            </a>
        </div>
        
        <p>{{ trans('plugins/hotel::ical.email.closing') }}</p>
        
        <hr style="border: none; border-top: 1px solid #e4e4e4; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #777;">
            {{ trans('plugins/hotel::ical.email.footer', ['site_name' => BaseHelper::getSiteName()]) }}
        </p>
    </div>
</body>
</html>
