<!doctype html>
<html lang="en">
<body style="margin:0;padding:0;background:#f4f1ec;font-family:Arial,Helvetica,sans-serif;color:#1b1f2a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ec;padding:24px 12px"><tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden">
    <tr><td style="background:#041433;padding:18px 24px;color:#ffffff;font-size:18px;font-weight:bold">
        @if($logoUrl)<img src="{{ $logoUrl }}" alt="" height="40" style="vertical-align:middle;margin-right:10px;background:#fff;border-radius:6px;padding:2px">@endif
        {{ $schoolName }}
    </td></tr>
    @if($imageUrl)<tr><td><img src="{{ $imageUrl }}" alt="" width="600" style="display:block;width:100%;height:auto"></td></tr>@endif
    <tr><td style="padding:24px">
        <h1 style="margin:0 0 14px;font-size:22px;color:#041433">{{ $heading }}</h1>
        @foreach(preg_split('/\R\s*\R/', trim($bodyText)) as $paragraph)
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#33384a">{!! nl2br(e($paragraph)) !!}</p>
        @endforeach
        @if($url && $buttonLabel)
            <p style="margin:22px 0 0"><a href="{{ $url }}" style="background:#7a141a;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:bold;display:inline-block">{{ $buttonLabel }}</a></p>
        @endif
    </td></tr>
    <tr><td style="padding:16px 24px;background:#faf8f5;font-size:12px;color:#6b7280">
        {{ $schoolName }}@if($address) &middot; {{ $address }}@endif
        @if($unsubscribeUrl)<br><a href="{{ $unsubscribeUrl }}" style="color:#6b7280">Unsubscribe from these emails</a>@endif
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
