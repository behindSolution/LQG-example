<?php

namespace App\Actions\QueryGate\Posts;

use App\Services\PostService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class UnfeaturePost extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'unfeature';
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
        return $request->user()->can('feature', $model);
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
        $post = app(PostService::class)->unfeature($model);

        return [
            'message' => 'Post is no longer featured',
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'is_featured' => $post->is_featured,
            ],
        ];
    }
}
