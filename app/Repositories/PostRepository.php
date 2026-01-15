<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PostRepository extends BaseRepository
{
    protected function resolveModel(): Model
    {
        return new Post();
    }

    public function findBySlug(string $slug): ?Post
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function getPublished(int $perPage = 15): CursorPaginator
    {
        return $this->query()
            ->published()
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->cursorPaginate($perPage);
    }

    public function getFeatured(int $limit = 5): Collection
    {
        return $this->query()
            ->published()
            ->featured()
            ->with(['author:id,name', 'category:id,name,slug'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function getByAuthor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->byAuthor($user->id)
            ->with(['category:id,name,slug', 'tags:id,name,slug'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByCategory(int $categoryId, int $perPage = 15): CursorPaginator
    {
        return $this->query()
            ->published()
            ->byCategory($categoryId)
            ->with(['author:id,name', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->cursorPaginate($perPage);
    }

    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->byStatus($status)
            ->with(['author:id,name', 'category:id,name,slug'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByTag(int $tagId, int $perPage = 15): CursorPaginator
    {
        return $this->query()
            ->published()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId))
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->cursorPaginate($perPage);
    }

    public function getPopular(int $limit = 10): Collection
    {
        return $this->query()
            ->published()
            ->with(['author:id,name', 'category:id,name,slug'])
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    public function getRelated(Post $post, int $limit = 5): Collection
    {
        return $this->query()
            ->published()
            ->where('id', '!=', $post->id)
            ->where(function (Builder $query) use ($post) {
                $query->where('category_id', $post->category_id)
                    ->orWhereHas('tags', fn ($q) => $q->whereIn('tags.id', $post->tags->pluck('id')));
            })
            ->with(['author:id,name', 'category:id,name,slug'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function incrementViews(Post $post): void
    {
        $post->increment('views_count');
    }

    public function incrementLikes(Post $post): void
    {
        $post->increment('likes_count');
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->published()
            ->where(function (Builder $query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%");
            })
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    public function getDrafts(User $user): Collection
    {
        return $this->query()
            ->byAuthor($user->id)
            ->byStatus(Post::STATUS_DRAFT)
            ->with(['category:id,name,slug'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getPending(): LengthAwarePaginator
    {
        return $this->query()
            ->byStatus(Post::STATUS_PENDING)
            ->with(['author:id,name', 'category:id,name,slug'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getArchivedCount(): int
    {
        return $this->query()
            ->byStatus(Post::STATUS_ARCHIVED)
            ->count();
    }

    public function getStatsByAuthor(User $user): array
    {
        $posts = $this->query()->byAuthor($user->id);

        return [
            'total' => $posts->count(),
            'published' => (clone $posts)->byStatus(Post::STATUS_PUBLISHED)->count(),
            'drafts' => (clone $posts)->byStatus(Post::STATUS_DRAFT)->count(),
            'pending' => (clone $posts)->byStatus(Post::STATUS_PENDING)->count(),
            'archived' => (clone $posts)->byStatus(Post::STATUS_ARCHIVED)->count(),
            'total_views' => (clone $posts)->sum('views_count'),
            'total_likes' => (clone $posts)->sum('likes_count'),
        ];
    }
}
