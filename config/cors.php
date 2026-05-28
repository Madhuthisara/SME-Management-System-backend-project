<?php

return [

   'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:3000','http://localhost:3001','https://raging-fire-e-store-fe.vercel.app','https://sme-management-system-frontend.vercel.app'], 

    'allowed_origins_patterns' => [],

 'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    'supports_credentials' => true, //true, 

];