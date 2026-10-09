<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class Robots extends Controller
{
    private const PRIVATE_PATHS = [
        '/administration',
        '/horizon',
        '/furnizor$',
        '/furnizor/',
        '/contul-meu',
        '/cere-oferta',
        '/anunturi/*/mesaje',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/autentificare-2-pasi',
        '/email',
        '/user',
        '/dashboard',
        '/cautare',
        '/localitati',
        '/api/',
    ];

    public function __invoke(): Response
    {
        // Keep staging/local environments out of search results entirely.
        $lines = app()->isProduction()
            ? ['User-agent: *', ...array_map(fn (string $path) => "Disallow: {$path}", self::PRIVATE_PATHS)]
            : ['User-agent: *', 'Disallow: /'];

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
