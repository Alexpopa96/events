<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    @php($seo = $page['props']['seo'] ?? null)
    {{-- With SSR running, @inertiaHead already renders the page <title>. --}}
    @unless (app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch())
        <title inertia>{{ $seo['full_title'] ?? config('app.name', 'Laravel') }}</title>
    @endunless
    @if ($seo)
        <meta name="description" content="{{ $seo['description'] }}">
        <meta name="robots" content="{{ $seo['robots'] }}">
        @if ($seo['canonical'])
            <link rel="canonical" href="{{ $seo['canonical'] }}">
            <meta property="og:url" content="{{ $seo['canonical'] }}">
        @endif
        <meta property="og:site_name" content="{{ \App\Support\Seo\Seo::BRAND }}">
        <meta property="og:locale" content="ro_RO">
        <meta property="og:type" content="{{ $seo['type'] }}">
        <meta property="og:title" content="{{ $seo['full_title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
        @if ($seo['image'])
            <meta property="og:image" content="{{ $seo['image'] }}">
        @endif
        @foreach ($seo['json_ld'] as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
        @endforeach
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..700&display=swap" rel="stylesheet"/>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Invita">
    <meta name="format-detection" content="telephone=no">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Nucleo Icons -->
    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Popper -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <!-- Main Styling -->
    <link href="/assets/css/argon-dashboard-tailwind.css?v=1.0.1" rel="stylesheet" />
    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
{{--<script src="/assets/js/sidenav-burger.js" async></script>--}}
{{--<script src="/assets/js/plugins/perfect-scrollbar.min.js" async></script>--}}
{{--<script src="/assets/js/argon-dashboard-tailwind.js?v=1.0.1" async></script>--}}
@inertia
</body>
</html>
