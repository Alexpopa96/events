@include('emails.notification', [
    'preheader' => "Codul tău este {$code} și este valabil {$validForMinutes} minute.",
    'badge' => 'Verificare email',
    'greeting' => "Salut, {$name}!",
    'lines' => ['Mulțumim că te-ai înregistrat pe EventHub. Introdu codul de mai jos în aplicație pentru a-ți confirma adresa de email.'],
    'validMinutes' => $validForMinutes,
    'footnotes' => [
        ['title' => 'Nu ai cerut acest cod?', 'text' => 'Poți ignora liniștit acest email — nimeni nu va putea accesa contul fără el.'],
        'Din motive de securitate, nu împărtăși codul cu nimeni. Echipa EventHub nu ți-l va cere niciodată.',
    ],
])
