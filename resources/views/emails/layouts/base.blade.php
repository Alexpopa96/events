{{--
    Shared EventHub email layout. Every email extends this view and fills the
    "content" section; the optional view data below drives the chrome:

      $subject    <title> and default preheader
      $preheader  hidden inbox-preview text
      $badge      small pill above the content
      $tone       success (default) | info | danger — colours the badge and accent bar
--}}
@php
    $tone = $tone ?? 'success';
    $palette = [
        'success' => ['bg' => '#FDE7EF', 'fg' => '#E11D63', 'from' => '#E11D63', 'to' => '#7C3AED'],
        'info' => ['bg' => '#F8F1E0', 'fg' => '#8A6420', 'from' => '#7C3AED', 'to' => '#A78BFA'],
        'danger' => ['bg' => '#FFF1F2', 'fg' => '#BE123C', 'from' => '#BE123C', 'to' => '#E11D63'],
    ][$tone] ?? ['bg' => '#FDE7EF', 'fg' => '#E11D63', 'from' => '#E11D63', 'to' => '#7C3AED'];

    $logoSrc = isset($message)
        ? $message->embed(public_path('assets/eventhub-logo.png'))
        : asset('assets/eventhub-logo.png');
    $sans = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $serif = "Georgia,'Iowan Old Style','Times New Roman',serif";
    $appUrl = rtrim(config('app.url'), '/');
@endphp
<!DOCTYPE html>
<html lang="ro" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $subject ?? 'EventHub' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#F7F5FC; font-family:{!! $sans !!}; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
<!-- Preheader -->
<div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#F7F5FC; font-size:1px; line-height:1px;">
    {{ $preheader ?? $subject ?? '' }}
    &#8199;&zwnj;&#8199;&zwnj;&#8199;&zwnj;&#8199;&zwnj;&#8199;&zwnj;&#8199;&zwnj;&#8199;&zwnj;&#8199;&zwnj;
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F5FC;">
    <tr>
        <td align="center" style="padding:40px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

                <!-- Logo -->
                <tr>
                    <td align="center" style="padding-bottom:28px;">
                        <a href="{{ $appUrl }}" target="_blank" style="text-decoration:none;">
                            <img src="{{ $logoSrc }}" alt="EventHub" width="168" style="display:block; width:168px; height:auto; border:0; outline:none;">
                        </a>
                    </td>
                </tr>

                <!-- Card -->
                <tr>
                    <td style="background-color:#FFFFFF; border:1px solid #EEEAF8; border-radius:24px; overflow:hidden; box-shadow:0 30px 60px -30px rgba(26,20,51,0.25);">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <!-- Accent bar -->
                            <tr>
                                <td style="height:4px; line-height:4px; font-size:0; background-color:{{ $palette['from'] }}; background-image:linear-gradient(90deg,{{ $palette['from'] }},{{ $palette['to'] }});">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding:40px 40px 36px 40px;">
                                    @isset($badge)
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 22px 0;">
                                            <tr>
                                                <td style="background-color:{{ $palette['bg'] }}; color:{{ $palette['fg'] }}; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; padding:6px 14px; border-radius:999px;">
                                                    {{ $badge }}
                                                </td>
                                            </tr>
                                        </table>
                                    @endisset

                                    @yield('content')
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="padding:28px 16px 0 16px;">
                        <p style="margin:0 0 4px 0; font-size:12px; line-height:1.6; color:#8F8AA6;">
                            &copy; {{ date('Y') }} <a href="{{ $appUrl }}" target="_blank" style="color:#8F8AA6; text-decoration:none;">EventHub</a> &mdash; platforma pentru servicii de evenimente
                        </p>
                        <p style="margin:0; font-size:12px; line-height:1.6; color:#8F8AA6;">
                            Ai primit acest email deoarece este asociat unui cont EventHub.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
