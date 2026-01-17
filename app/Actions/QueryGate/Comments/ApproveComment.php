<?php

namespace App\Actions\QueryGate\Comments;

use App\Services\CommentService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class ApproveComment extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'approve';
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
        return [];
    }

    public function openapiRequest(): array
    {
        return [];
    }

    public function handle($request, $model, array $payload)
    {
        $comment = app(CommentService::class)->approve($model);

        return [
            'message' => 'Comment approved successfully',
            'comment' => [
                'id' => $comment->id,
                'status' => $comment->status,
                'content' => $comment->content,
                'author' => $comment->getAuthorDisplayName(),
            ],
        ];
    }
}
