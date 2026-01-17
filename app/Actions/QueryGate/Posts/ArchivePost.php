<?php

namespace App\Actions\QueryGate\Posts;

use App\Services\PostService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class ArchivePost extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'archive';
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
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function openapiRequest(): array
    {
        return [
            'reason' => fake()->sentence(),
        ];
    }

    public function handle($request, $model, array $payload)
    {
        $post = app(PostService::class)->archive($model);

        return [
            'message' => 'Post archived successfully',
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
            ],
            'archived_at' => now()->toIso8601String(),
            'reason' => $payload['reason'] ?? null,
        ];
    }
}
