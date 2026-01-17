<?php

namespace App\Actions\QueryGate\Comments;

use App\Services\CommentService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class RejectComment extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'reject';
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
        return $request->user()?->can('moderate', $model) ?? false;
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
        $comment = app(CommentService::class)->reject($model);

        return [
            'message' => 'Comment rejected',
            'comment' => [
                'id' => $comment->id,
                'status' => $comment->status,
            ],
            'reason' => $payload['reason'] ?? null,
        ];
    }
}
