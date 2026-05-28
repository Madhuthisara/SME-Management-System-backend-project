<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    // 'allowed_origins' => ['http://localhost:3000','http://localhost:3001','https://raging-fire-e-store-fe.vercel.app','https://sme-management-system-frontend-hvjqbrle1.vercel.app','https://sme-management-system-frontend-hvjqbrle1.vercel.app'], 

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, //true, 

];