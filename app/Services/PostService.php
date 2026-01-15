<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostService
{
    public function __construct(
        protected PostRepository $repository
    ) {}

    public function create(User $author, array $data): Post
    {
        return DB::transaction(function () use ($author, $data) {
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->ensureUniqueSlug($slug);

            $post = $this->repository->create([
                'user_id' => $author->id,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => $slug,
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'],
                'featured_image' => $data['featured_image'] ?? null,
                'status' => $data['status'] ?? Post::STATUS_DRAFT,
            ]);

            if (!empty($data['tags'])) {
                $post->tags()->sync($data['tags']);
            }

            $this->clearCache();

            return $post->load(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug']);
        });
    }

    public function update(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data) {
            if (isset($data['slug']) && $data['slug'] !== $post->slug) {
                $data['slug'] = $this->ensureUniqueSlug($data['slug'], $post->id);
            } elseif (isset($data['title']) && !isset($data['slug'])) {
                $newSlug = Str::slug($data['title']);
                if ($newSlug !== $post->slug) {
                    $data['slug'] = $this->ensureUniqueSlug($newSlug, $post->id);
                }
            }

            $this->repository->update($post, $data);

            if (array_key_exists('tags', $data)) {
                $post->tags()->sync($data['tags'] ?? []);
            }

            $this->clearCache();

            return $post->fresh(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug']);
        });
    }

    public function publish(Post $post): Post
    {
        if (!$post->canBePublished()) {
            throw new HttpException(422,"Post cannot be published from status: {$post->status}");
        }

        $this->repository->update($post, [
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->clearCache();

        return $post->fresh();
    }

    public function unpublish(Post $post): Post
    {
        if (!$post->isPublished()) {
            throw new HttpException(422,'Post is not published');
        }

        $this->repository->update($post, [
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $this->clearCache();

        return $post->fresh();
    }

    public function archive(Post $post): Post
    {
        if ($post->status === Post::STATUS_ARCHIVED) {
            throw new HttpException(422,'Post is already archived');
        }

        $this->repository->update($post, [
            'status' => Post::STATUS_ARCHIVED,
        ]);

        $this->clearCache();

        return $post->fresh();
    }

    public function feature(Post $post): Post
    {
        if ($post->is_featured) {
            throw new HttpException(422,'Post is already featured');
        }

        $this->repository->update($post, [
            'is_featured' => true,
        ]);

        $this->clearCache();

        return $post->fresh();
    }

    public function unfeature(Post $post): Post
    {
        if (!$post->is_featured) {
            throw new HttpException(422,'Post is not featured');
        }

        $this->repository->update($post, [
            'is_featured' => false,
        ]);

        $this->clearCache();

        return $post->fresh();
    }

    public function duplicate(Post $post, ?User $newAuthor = null): Post
    {
        return DB::transaction(function () use ($post, $newAuthor) {
            $newSlug = $this->ensureUniqueSlug($post->slug . '-copy');

            $newPost = $this->repository->create([
                'user_id' => $newAuthor?->id ?? $post->user_id,
                'category_id' => $post->category_id,
                'title' => $post->title . ' (Copy)',
                'slug' => $newSlug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featured_image' => $post->featured_image,
                'status' => Post::STATUS_DRAFT,
                'is_featured' => false,
            ]);

            $newPost->tags()->sync($post->tags->pluck('id'));

            $this->clearCache();

            return $newPost->load(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug']);
        });
    }

    public function bulkPublish(array $postIds): array
    {
        $results = [
            'published' => [],
            'failed' => [],
        ];

        DB::transaction(function () use ($postIds, &$results) {
            foreach ($postIds as $id) {
                $post = $this->repository->find($id);

                if (!$post) {
                    $results['failed'][] = ['id' => $id, 'reason' => 'Post not found'];
                    continue;
                }

                if (!$post->canBePublished()) {
                    $results['failed'][] = ['id' => $id, 'reason' => "Cannot publish from status: {$post->status}"];
                    continue;
                }

                $this->repository->update($post, [
                    'status' => Post::STATUS_PUBLISHED,
                    'published_at' => now(),
                ]);

                $results['published'][] = $id;
            }
        });

        if (!empty($results['published'])) {
            $this->clearCache();
        }

        return $results;
    }

    public function delete(Post $post): bool
    {
        $result = $this->repository->delete($post);
        $this->clearCache();

        return $result;
    }

    protected function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Post::withTrashed()->where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    protected function clearCache(): void
    {
        Cache::forget('posts-list');
    }
}
