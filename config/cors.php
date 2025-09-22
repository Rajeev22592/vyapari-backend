<?php

return [
	'paths' => ['api/*', 'sanctum/csrf-cookie'],

	'allowed_methods' => ['*'],

	// Set comma-separated origins in .env: FRONTEND_ORIGINS="http://localhost:5173,https://vyapari-zen-hub.vercel.app"
	'allowed_origins' => array_filter(array_map('trim', explode(',', env('FRONTEND_ORIGINS', '*')))),

	'allowed_origins_patterns' => [],

	'allowed_headers' => ['*'],

	'exposed_headers' => ['*'],

	'max_age' => 600,

	'supports_credentials' => true,
];

