{{--
    Generic EventHub email. View data (see also emails/layouts/base):

      $greeting     serif heading
      $lines        array of paragraphs
      $code         optional one-time code, shown in a highlighted box
      $validMinutes optional, validity note under the code
      $actionUrl / $actionText   optional call-to-action button
      $footnotes    optional array of small print lines (security notes etc.)
--}}
@extends('emails.layouts.base')

@php
    $sans = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $serif = "Georgia,'Iowan Old Style','Times New Roman',serif";
@endphp

@section('content')
    <h1 style="margin:0 0 14px 0; font-family:{!! $serif !!}; font-size:28px; line-height:1.25; font-weight:normal; color:#1A1433;">
        {{ $greeting }}
    </h1>

    @foreach ($lines as $line)
        <p style="margin:0 0 16px 0; font-size:15px; line-height:1.7; color:#585370;">
            {{ $line }}
        </p>
    @endforeach

    @isset($code)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:12px 0 0 0;">
            <tr>
                <td align="center" style="background-color:#F7F5FC; border:1px solid #EEEAF8; border-radius:18px; padding:26px 12px;">
                    <div style="font-family:'SF Mono',SFMono-Regular,Menlo,Consolas,'Courier New',monospace; font-size:40px; line-height:1; font-weight:700; letter-spacing:12px; text-indent:12px; color:#E11D63;">{{ $code }}</div>
                </td>
            </tr>
        </table>

        @isset($validMinutes)
            <p style="margin:18px 0 0 0; text-align:center; font-size:13px; line-height:1.6; color:#8F8AA6;">
                Codul este valabil <strong style="color:#1A1433;">{{ $validMinutes }} minute</strong>.
            </p>
        @endisset
    @endisset

    @isset($actionUrl)
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0 0 0;">
            <tr>
                <td align="center" bgcolor="#E11D63" style="border-radius:999px; background-color:#E11D63; background-image:linear-gradient(180deg,#F0306F,#E11D63);">
                    <a href="{{ $actionUrl }}" target="_blank" style="display:inline-block; padding:15px 34px; font-family:{!! $sans !!}; font-size:14px; font-weight:600; color:#FFFFFF; text-decoration:none; border-radius:999px;">
                        {{ $actionText ?? 'Deschide' }}
                    </a>
                </td>
            </tr>
        </table>

        <p style="margin:20px 0 0 0; font-size:12px; line-height:1.6; color:#8F8AA6; word-break:break-all;">
            Dacă butonul nu funcționează, copiază acest link în browser:<br>
            <a href="{{ $actionUrl }}" target="_blank" style="color:#E11D63; text-decoration:underline;">{{ $actionUrl }}</a>
        </p>
    @endisset

    @if (! empty($footnotes))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:32px 0 24px 0;">
            <tr><td style="height:1px; line-height:1px; font-size:0; background-color:#EEEAF8;">&nbsp;</td></tr>
        </table>

        @foreach ($footnotes as $footnote)
            <p style="margin:0 0 10px 0; font-size:13px; line-height:1.65; color:#585370;">
                @if (is_array($footnote))
                    <strong style="color:#1A1433;">{{ $footnote['title'] }}</strong>
                    {{ $footnote['text'] }}
                @else
                    {{ $footnote }}
                @endif
            </p>
        @endforeach
    @endif
@endsection
