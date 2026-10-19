@extends('emails.hotel-layout')
@section('body')
<p style="margin:0 0 22px;font-size:16px;line-height:1.75;color:#5a4637;">{{ $copy['alert_intro'] }}</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;background:#f3e8d6;border-radius:10px;">
  <tr><td style="padding:16px 20px;font-size:14px;line-height:1.9;color:#4e3426;">
    <strong style="color:#2b1e17;">{{ $copy['alert_time'] }}:</strong> <span dir="ltr">{{ $time }} UTC</span><br>
    <strong style="color:#2b1e17;">{{ $copy['alert_ip'] }}:</strong> <span dir="ltr">{{ $ip }}</span><br>
    <strong style="color:#2b1e17;">{{ $copy['alert_device'] }}:</strong> <span dir="ltr">{{ $device }}</span>
  </td></tr>
</table>
<p style="margin:0;font-size:14px;line-height:1.75;color:#5a4637;">{{ $copy['alert_next'] }}</p>
@endsection
