<?php

namespace App\Actions\QueryGate\Posts;

use App\Models\Post;
use App\Services\PostService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class BulkPublishPosts extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'bulk-publish';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function status(): ?int
    {
        return 200;
    }

    public function requiresModel(): bool
    {
        return false;
    }

    public function authorize($request, $model): ?bool
    {
        return $request->user()->can('create', Post::class);
    }

    public function validations(): array
    {
        return [
            'post_ids' => ['required', 'array', 'min:1', 'max:50'],
            'post_ids.*' => ['required', 'integer', 'exists:posts,id'],
        ];
    }

    public function openapiRequest(): array
    {
        return [
            'post_ids' => [fake()->randomNumber(), fake()->randomNumber(), fake()->randomNumber()],
        ];
    }

    public function handle($request, $model, array $payload)
    {
        $results = app(PostService::class)->bulkPublish($payload['post_ids']);

        return [
            'message' => 'Bulk publish completed',
            'summary' => [
                'requested' => count($payload['post_ids']),
                'published' => count($results['published']),
                'failed' => count($results['failed']),
            ],
            'published_ids' => $results['published'],
            'failures' => $results['failed'],
        ];
    }
}
