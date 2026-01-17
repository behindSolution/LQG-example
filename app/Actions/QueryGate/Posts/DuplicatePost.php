<?php

namespace App\Actions\QueryGate\Posts;

use App\Services\PostService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class DuplicatePost extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'duplicate';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function status(): ?int
    {
        return 201;
    }

    public function authorize($request, $model): ?bool
    {
        return $request->user()->can('create', $model);
    }

    public function validations(): array
    {
        return [
            'new_title' => ['nullable', 'string', 'max:255'],
            'assign_to_me' => ['nullable', 'boolean'],
        ];
    }

    public function openapiRequest(): array
    {
        return [
            'new_title' => fake()->sentence(),
            'assign_to_me' => fake()->boolean(),
        ];
    }

    public function handle($request, $model, array $payload)
    {
        $assignToMe = $payload['assign_to_me'] ?? true;
        $newAuthor = $assignToMe ? $request->user() : null;

        $newPost = app(PostService::class)->duplicate($model, $newAuthor);

        if (!empty($payload['new_title'])) {
            $newPost->update(['title' => $payload['new_title']]);
            $newPost->refresh();
        }

        return [
            'message' => 'Post duplicated successfully',
            'original_id' => $model->id,
            'new_post' => [
                'id' => $newPost->id,
                'title' => $newPost->title,
                'slug' => $newPost->slug,
                'status' => $newPost->status,
                'author' => [
                    'id' => $newPost->author->id,
                    'name' => $newPost->author->name,
                ],
            ],
        ];
    }
}
