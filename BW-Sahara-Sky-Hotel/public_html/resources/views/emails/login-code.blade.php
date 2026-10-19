@extends('emails.hotel-layout')
@section('body')
<p style="margin:0 0 26px;font-size:16px;line-height:1.75;color:#5a4637;">{{ $copy['code_intro'] }}</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">
  <tr>
    <td align="center" bgcolor="#2b1e17" style="background:#2b1e17;border-radius:12px;padding:26px 16px 24px;border:1px solid #4e3426;">
      <div style="font-family:{{ $copy['font_body'] }};font-size:11px;letter-spacing:{{ $dir === 'rtl' ? '0' : '3px' }};color:#b99a74;margin-bottom:12px;">{{ $copy['code_label'] }}</div>
      <div class="code" dir="ltr" style="font-family:'Courier New',Courier,monospace;font-size:42px;line-height:1;letter-spacing:10px;font-weight:bold;color:#e5c194;padding-left:10px;">
{{ $code }}
      </div>
    </td>
  </tr>
</table>
<p style="margin:14px 0 26px;font-size:14px;line-height:1.7;color:#8a7258;text-align:center;">{{ str_replace(':minutes', (string) $minutes, $copy['code_expiry']) }}</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="background:#f3e8d6;border-{{ $dir === 'rtl' ? 'right' : 'left' }}:4px solid #c9a06a;border-radius:8px;padding:15px 18px;font-size:13px;line-height:1.7;color:#5a4637;">
      <strong style="color:#2b1e17;">{{ $copy['code_safety_title'] }}.</strong> {{ $copy['code_safety'] }}
    </td>
  </tr>
</table>
@endsection
