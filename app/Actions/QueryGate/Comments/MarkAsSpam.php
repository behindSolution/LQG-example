<?php

namespace App\Actions\QueryGate\Comments;

use App\Services\CommentService;
use BehindSolution\LaravelQueryGate\Actions\AbstractQueryGateAction;

class MarkAsSpam extends AbstractQueryGateAction
{
    public function action(): string
    {
        return 'mark-spam';
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
        $comment = app(CommentService::class)->markAsSpam($model);

        return [
            'message' => 'Comment marked as spam',
            'comment' => [
                'id' => $comment->id,
                'status' => $comment->status,
                'ip_address' => $comment->ip_address,
            ],
        ];
    }
}
