<?php

namespace App\Actions\QueryGate\Posts;

use App\Services\PostService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class UnpublishPost extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'unpublish';
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

    public function handle($request, $model, array $payload)
    {
        $post = app(PostService::class)->unpublish($model);

        return [
            'message' => 'Post unpublished successfully',
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'published_at' => null,
            ],
        ];
    }
}
