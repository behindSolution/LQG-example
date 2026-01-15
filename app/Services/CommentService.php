<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CommentRepository;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CommentService
{
    public function __construct(
        protected CommentRepository $repository
    ) {}

    public function create(Post $post, array $data, ?User $author = null): Comment
    {
        $comment = $this->repository->create([
            'post_id' => $post->id,
            'user_id' => $author?->id,
            'parent_id' => $data['parent_id'] ?? null,
            'author_name' => $data['author_name'] ?? $author?->name,
            'author_email' => $data['author_email'] ?? $author?->email,
            'content' => $data['content'],
            'status' => $author ? Comment::STATUS_APPROVED : Comment::STATUS_PENDING,
            'ip_address' => $data['ip_address'] ?? null,
        ]);

        $this->clearCache($post);

        return $comment->load(['post:id,title,slug', 'author:id,name']);
    }

    public function update(Comment $comment, array $data): Comment
    {
        $this->repository->update($comment, $data);
        $this->clearCache($comment->post);

        return $comment->fresh(['post:id,title,slug', 'author:id,name']);
    }

    public function approve(Comment $comment): Comment
    {
        if ($comment->isApproved()) {
            throw new HttpException(422, 'Comment is already approved');
        }

        $this->repository->approve($comment);
        $this->clearCache($comment->post);

        return $comment->fresh();
    }

    public function reject(Comment $comment): Comment
    {
        if ($comment->status === Comment::STATUS_REJECTED) {
            throw new HttpException(422, 'Comment is already rejected');
        }

        $this->repository->reject($comment);
        $this->clearCache($comment->post);

        return $comment->fresh();
    }

    public function markAsSpam(Comment $comment): Comment
    {
        if ($comment->status === Comment::STATUS_SPAM) {
            throw new HttpException(422, 'Comment is already marked as spam');
        }

        $this->repository->markAsSpam($comment);
        $this->clearCache($comment->post);

        return $comment->fresh();
    }

    public function bulkApprove(array $ids): int
    {
        $count = $this->repository->bulkApprove($ids);
        Cache::forget('comments-list');

        return $count;
    }

    public function delete(Comment $comment): bool
    {
        $post = $comment->post;
        $result = $this->repository->delete($comment);
        $this->clearCache($post);

        return $result;
    }

    protected function clearCache(Post $post): void
    {
        Cache::forget('comments-list');
        Cache::forget("post-{$post->id}-comments");
    }
}
