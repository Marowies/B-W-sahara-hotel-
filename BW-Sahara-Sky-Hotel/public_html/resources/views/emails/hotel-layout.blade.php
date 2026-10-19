<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title>{{ $title }}</title>
<!--[if mso]><style>td,p,a,span{font-family:Arial,sans-serif!important}</style><![endif]-->
<style>
@media only screen and (max-width:620px){
  .shell{width:100%!important}
  .pad{padding-left:22px!important;padding-right:22px!important}
  .code{font-size:34px!important;letter-spacing:6px!important}
}
</style>
</head>
<body style="margin:0;padding:0;background:#efe4d3;-webkit-text-size-adjust:100%;">
{{-- Preview text shown next to the subject in the inbox list. --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#efe4d3;font-size:1px;line-height:1px;">{{ $preheader }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#efe4d3;">
  <tr>
    <td align="center" style="padding:28px 12px;">
      <table role="presentation" class="shell" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:#fbf6ee;border-radius:14px;overflow:hidden;border:1px solid #dcc7a8;">
        <tr>
          <td align="center" bgcolor="#2b1e17" style="background:#2b1e17;padding:34px 24px 26px;">
            <img src="{{ $logo }}" width="170" alt="{{ $copy['brand'] }}" style="display:block;border:0;outline:none;width:170px;height:auto;margin:0 auto;">
            <div style="font-family:{{ $copy['font'] }};color:#e5c194;font-size:19px;letter-spacing:5px;margin-top:16px;">{{ $copy['brand'] }}</div>
            <div style="font-family:{{ $copy['font_body'] }};color:#b99a74;font-size:10px;letter-spacing:4px;margin-top:7px;">{{ $copy['tagline'] }}</div>
          </td>
        </tr>
        <tr><td height="3" style="height:3px;line-height:3px;font-size:0;background:#c9a06a;background-image:linear-gradient(90deg,#8a5a34,#e5c194,#8a5a34);">&nbsp;</td></tr>
        <tr>
          <td class="pad" style="padding:38px 44px 10px;font-family:{{ $copy['font_body'] }};color:#372b24;text-align:{{ $dir === 'rtl' ? 'right' : 'left' }};">
            <h1 style="margin:0 0 14px;font-family:{{ $copy['font'] }};font-weight:normal;font-size:28px;line-height:1.25;color:#2b1e17;">{{ $title }}</h1>
            @yield('body')
          </td>
        </tr>
        <tr>
          <td class="pad" style="padding:18px 44px 34px;font-family:{{ $copy['font_body'] }};text-align:center;">
            <div style="border-top:1px solid #e3d3ba;padding-top:20px;color:#8a7258;font-size:12px;line-height:1.7;">
              <strong style="color:#4e3426;letter-spacing:2px;font-size:11px;">{{ $copy['brand'] }}</strong><br>
              {{ $copy['footer_place'] }}<br>
              <span dir="ltr">+20 109 825 5777</span> &nbsp;·&nbsp; <a href="{{ $siteUrl }}" style="color:#875435;text-decoration:none;">{{ parse_url($siteUrl, PHP_URL_HOST) }}</a><br>
              <span style="color:#a5927a;">{{ $copy['footer_note'] }} {{ $copy['footer_auto'] }}</span>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
