<?php

// Permite definir orígenes en una variable de entorno separada.
$origins = array_values(array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))));

return [

    // Aplica CORS a la API (y al endpoint de Sanctum si algún día usas cookies)
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Métodos permitidos
    'allowed_methods' => ['*'],

    // Orígenes permitidos (dominios del front)
    // Ej: https://tu-front.vercel.app, http://localhost:5173
    'allowed_origins' => $origins,

    // Si prefieres permitir todos en desarrollo, pon '*' aquí:
    // 'allowed_origins' => env('APP_ENV') === 'local' ? ['*'] : $origins,

    // Encabezados permitidos
    'allowed_headers' => ['*'],

    // Encabezados expuestos al navegador (normalmente ninguno)
    'exposed_headers' => [],

    // Prefetch cache
    'max_age' => 0,

    // IMPORTANTE: usas tokens Bearer, no cookies -> false
    'supports_credentials' => false,

    // (Opcional) Permitir previews de Vercel con regex
    'allowed_origins_patterns' => [
        // '#^https://.*\.vercel\.app$#',
    ],
];
