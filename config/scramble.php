<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api',

    'api_domain' => null,

    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => <<<'MD'
# Blog Open AI API

REST API for blog posts and OpenAI-powered image prompt generation.

## Authentication

Protected endpoints require a Sanctum bearer token:

```
Authorization: Bearer {your-token}
```

Obtain a token via `POST /api/login`.

## Base URL

All routes are prefixed with `/api`.
MD,
    ],

    'ui' => [
        'title' => env('APP_NAME', 'Blog Open AI').' API',
        'theme' => 'light',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],

    'servers' => null,

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
