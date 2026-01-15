<?php

use BehindSolution\LaravelQueryGate\Support\QueryGate;

return [

    'route' => [
        'prefix' => 'query',
        'middleware' => ['throttle:60,1'],
    ],

    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    'openAPI' => [
        'enabled' => true,
        'title' => 'Blog API - Query Gate',
        'description' => 'Complete blog API with posts, comments, categories, and tags management.',
        'version' => '2.0.0',
        'route' => 'query/docs',
        'json_route' => 'query/docs.json',
        'ui' => 'redoc',
        'ui_options' => [
            'hideDownloadButton' => false,
            'expandResponses' => '200,201',
        ],
        'servers' => [
            ['url' => 'http://localhost:8000', 'description' => 'Local Development'],
        ],
        'output' => [
            'format' => 'json',
            'path' => storage_path('app/query-gate-openapi.json'),
        ],
        'auth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearer_format' => 'JWT',
        ],
        'tags' => [
            ['name' => 'Posts', 'description' => 'Blog posts management'],
            ['name' => 'Comments', 'description' => 'Comments moderation'],
            ['name' => 'Categories', 'description' => 'Post categories'],
            ['name' => 'Tags', 'description' => 'Post tags'],
            ['name' => 'Users', 'description' => 'User management'],
        ],
        'middleware' => [],
        'modifiers' => [],
    ],

    'models' => [
        App\Models\User::class,
        App\Models\Post::class,
        App\Models\Comment::class,
        App\Models\Category::class,
        App\Models\Tag::class,
    ],

];

