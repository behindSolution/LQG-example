<?php

namespace App\Actions\QueryGate\Posts;

use App\Services\PostService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class PublishPost extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'publish';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function status(): ?int
    {
        return 200;
    }

    public function authorize($request, $model): ?bool
    {
        return $request->user()->can('update', $model);
    }

    public function validations(): array
    {
        return [];
    }

    public function openapiRequest(): array
    {
        return [];
    }

    public function handle($request, $model, array $payload)
    {
        $post = app(PostService::class)->publish($model);

        return [
            'message' => 'Post published successfully',
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'published_at' => $post->published_at?->toIso8601String(),
            ],
        ];
    }
}
