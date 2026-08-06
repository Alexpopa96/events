@php
    $tonePalette = [
        'success' => ['bg' => '#F5F3FF', 'fg' => '#6D28D9'],
        'info' => ['bg' => '#FFFBEB', 'fg' => '#B45309'],
        'danger' => ['bg' => '#FBEAE8', 'fg' => '#B3413A'],
    ][$tone ?? 'success'];
@endphp
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? '' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#EAEAEE; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EAEAEE; padding:40px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
                <!-- Wordmark -->
                <tr>
                    <td align="center" style="padding-bottom:28px;">
                        <span style="font-family:Georgia,'Iowan Old Style','Times New Roman',serif; font-size:22px; color:#211C27;">
                            Event<span style="color:#7C3AED;">Hub</span>
                        </span>
                    </td>
                </tr>

                <!-- Card -->
                <tr>
                    <td style="background-color:#FFFFFF; border-radius:24px; box-shadow:0 24px 60px -20px rgba(33,28,39,0.18); overflow:hidden;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding:40px 40px 32px 40px;">
                                    @isset($badge)
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                                            <tr>
                                                <td style="background-color:{{ $tonePalette['bg'] }}; color:{{ $tonePalette['fg'] }}; font-size:12px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; padding:6px 14px; border-radius:999px;">
                                                    {{ $badge }}
                                                </td>
                                            </tr>
                                        </table>
                                    @endisset

                                    <h1 style="margin:0 0 16px 0; font-family:Georgia,'Iowan Old Style','Times New Roman',serif; font-size:24px; line-height:1.3; color:#211C27;">
                                        {{ $greeting }}
                                    </h1>

                                    @foreach ($lines as $line)
                                        <p style="margin:0 0 16px 0; font-size:15px; line-height:1.65; color:#6B6373;">
                                            {{ $line }}
                                        </p>
                                    @endforeach

                                    @isset($actionUrl)
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0 8px 0;">
                                            <tr>
                                                <td style="border-radius:999px; background-color:#7C3AED;">
                                                    <a href="{{ $actionUrl }}" target="_blank" style="display:inline-block; padding:13px 28px; font-size:14px; font-weight:600; color:#FFFFFF; text-decoration:none; border-radius:999px;">
                                                        {{ $actionText }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    @endisset
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="padding-top:28px;">
                        <p style="margin:0; font-size:12px; line-height:1.6; color:#6B6373;">
                            &copy; {{ date('Y') }} EventHub &mdash; platforma pentru servicii de evenimente
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
