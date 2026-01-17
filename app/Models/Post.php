<?php

namespace App\Models;

use App\Actions\QueryGate\Posts\ArchivePost;
use App\Actions\QueryGate\Posts\BulkPublishPosts;
use App\Actions\QueryGate\Posts\DuplicatePost;
use App\Actions\QueryGate\Posts\FeaturePost;
use App\Actions\QueryGate\Posts\PublishPost;
use App\Actions\QueryGate\Posts\UnfeaturePost;
use App\Actions\QueryGate\Posts\UnpublishPost;
use App\Services\PostService;
use BehindSolution\LaravelQueryGate\Support\QueryGate;
use BehindSolution\LaravelQueryGate\Traits\HasQueryGate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, HasQueryGate, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'is_featured',
        'views_count',
        'likes_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('status', 'approved');
    }

    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeByAuthor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // Helpers
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBePublished(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    public static function queryGate(): QueryGate
    {
        return QueryGate::make()
            ->alias('posts')
            ->cache(120, 'posts-list')
            ->paginationMode('cursor')
            ->openapiResponse([
                'id' => fake()->randomNumber(),
                'title' => fake()->sentence(),
                'slug' => fake()->slug(),
                'excerpt' => fake()->paragraph(),
                'content' => fake()->paragraphs(3, true),
                'featured_image' => fake()->imageUrl(),
                'status' => fake()->randomElement(['draft', 'pending', 'published', 'archived']),
                'is_featured' => fake()->boolean(),
                'views_count' => fake()->randomNumber(4),
                'likes_count' => fake()->randomNumber(3),
                'created_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
                'updated_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
                'published_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
                'author.id' => fake()->randomNumber(),
                'author.name' => fake()->name(),
                'author.email' => fake()->safeEmail(),
                'category.id' => fake()->randomNumber(),
                'category.name' => fake()->word(),
                'category.slug' => fake()->slug(),
                'tags.id' => fake()->randomNumber(),
                'tags.name' => fake()->word(),
                'tags.slug' => fake()->slug(),
            ])
            ->query(fn ($query, $request) => $query->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug']))

            // Version 1: Basic API
            ->version('2024-01-01', function (QueryGate $gate) {
                $gate->filters([
                    'title' => ['string', 'max:255'],
                    'status' => ['string', 'in:draft,pending,published,archived'],
                    'created_at' => 'date',
                ])
                ->allowedFilters([
                    'title' => ['eq', 'like'],
                    'status' => ['eq'],
                    'created_at' => ['gte', 'lte'],
                ])
                ->select(['id', 'title', 'slug', 'status', 'created_at'])
                ->sorts(['created_at', 'title']);
            })

            // Version 2: Extended filters and relations
            ->version('2024-06-01', function (QueryGate $gate) {
                $gate->filters([
                    'title' => ['string', 'max:255'],
                    'status' => ['string', 'in:draft,pending,published,archived'],
                    'is_featured' => 'boolean',
                    'user_id' => 'integer',
                    'category_id' => 'integer',
                    'created_at' => 'date',
                    'published_at' => 'date',
                    'author.name' => ['string', 'max:100'],
                    'category.slug' => ['string', 'max:100'],
                ])
                ->allowedFilters([
                    'title' => ['eq', 'like'],
                    'status' => ['eq', 'in', 'neq'],
                    'is_featured' => ['eq'],
                    'user_id' => ['eq'],
                    'category_id' => ['eq', 'in'],
                    'created_at' => ['gte', 'lte', 'between'],
                    'published_at' => ['gte', 'lte', 'between'],
                    'author.name' => ['like'],
                    'category.slug' => ['eq'],
                ])
                ->select([
                    'id', 'title', 'slug', 'excerpt', 'status',
                    'is_featured', 'views_count', 'created_at', 'published_at',
                    'author.name', 'category.name', 'category.slug',
                ])
                ->sorts(['created_at', 'published_at', 'title', 'views_count']);
            })

            // Version 3: Full featured with advanced filtering
            ->version('2025-01-01', function (QueryGate $gate) {
                $gate->filters([
                    'title' => ['string', 'max:255'],
                    'slug' => ['string', 'max:255'],
                    'status' => ['string', 'in:draft,pending,published,archived'],
                    'is_featured' => 'boolean',
                    'user_id' => 'integer',
                    'category_id' => 'integer',
                    'views_count' => 'integer',
                    'likes_count' => 'integer',
                    'created_at' => 'date',
                    'updated_at' => 'date',
                    'published_at' => 'date',
                    'author.name' => ['string', 'max:100'],
                    'author.email' => ['string', 'email'],
                    'category.slug' => ['string', 'max:100'],
                    'category.is_active' => 'boolean',
                    'tag_slugs' => ['string', 'max:255'],
                    'comment_status' => ['string', 'in:pending,approved,spam,rejected'],
                ])
                ->allowedFilters([
                    'title' => ['eq', 'like', 'neq'],
                    'slug' => ['eq'],
                    'status' => ['eq', 'in', 'neq', 'not_in'],
                    'is_featured' => ['eq'],
                    'user_id' => ['eq', 'in'],
                    'category_id' => ['eq', 'in', 'neq'],
                    'views_count' => ['eq', 'gt', 'gte', 'lt', 'lte', 'between'],
                    'likes_count' => ['eq', 'gt', 'gte', 'lt', 'lte', 'between'],
                    'created_at' => ['eq', 'gt', 'gte', 'lt', 'lte', 'between'],
                    'updated_at' => ['eq', 'gt', 'gte', 'lt', 'lte', 'between'],
                    'published_at' => ['eq', 'gt', 'gte', 'lt', 'lte', 'between'],
                    'author.name' => ['eq', 'like'],
                    'author.email' => ['eq', 'like'],
                    'category.slug' => ['eq', 'in'],
                    'category.is_active' => ['eq'],
                    'tag_slugs' => ['eq', 'in'],
                    'comment_status' => ['eq', 'in'],
                ])
                ->rawFilters([
                    'title' => fn ($builder, $operator, $value, $column) =>
                        $operator === 'like'
                            ? $builder->where($column, 'like', '%' . $value . '%')
                            : $builder->where($column, $operator === 'eq' ? '=' : '!=', $value),

                    'tag_slugs' => fn ($builder, $operator, $value, $column) =>
                        $builder->whereHas('tags', function ($q) use ($operator, $value) {
                            if ($operator === 'in') {
                                $q->whereIn('slug', is_array($value) ? $value : explode(',', $value));
                            } else {
                                $q->where('slug', $value);
                            }
                        }),

                    'comment_status' => fn ($builder, $operator, $value, $column) =>
                        $builder->whereHas('comments', function ($q) use ($operator, $value) {
                            if ($operator === 'in') {
                                $q->whereIn('status', is_array($value) ? $value : explode(',', $value));
                            } else {
                                $q->where('status', $value);
                            }
                        }),
                ])
                ->select([
                    'id', 'title', 'slug', 'excerpt', 'content', 'featured_image',
                    'status', 'is_featured', 'views_count', 'likes_count',
                    'created_at', 'updated_at', 'published_at',
                    'author.id', 'author.name', 'author.email',
                    'category.id', 'category.name', 'category.slug',
                    'tags.id', 'tags.name', 'tags.slug',
                ])
                ->sorts(['created_at', 'updated_at', 'published_at', 'title', 'views_count', 'likes_count']);
            })

            ->middleware(['auth:sanctum'])

            // Actions
            ->actions(fn ($actions) => $actions
                ->create(fn ($action) => $action
                    ->validations([
                        'title' => ['required', 'string', 'max:255'],
                        'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
                        'excerpt' => ['nullable', 'string', 'max:500'],
                        'content' => ['required', 'string'],
                        'category_id' => ['nullable', 'exists:categories,id'],
                        'featured_image' => ['nullable', 'string', 'url', 'max:500'],
                        'status' => ['nullable', 'in:draft,pending'],
                        'tags' => ['nullable', 'array'],
                        'tags.*' => ['exists:tags,id'],
                    ])
                    ->openapiRequest([
                        'title' => fake()->sentence(),
                        'slug' => fake()->slug(),
                        'excerpt' => fake()->paragraph(),
                        'content' => fake()->paragraphs(3, true),
                        'category_id' => fake()->randomNumber(),
                        'featured_image' => fake()->imageUrl(),
                        'status' => fake()->randomElement(['draft', 'pending']),
                        'tags' => [fake()->randomNumber(), fake()->randomNumber()],
                    ])
                    ->handle(function ($request, $model, $payload) {
                        return app(PostService::class)->create(
                            $request->user(),
                            $payload
                        );
                    })
                )
                ->update(fn ($action) => $action
                    ->validations([
                        'title' => ['sometimes', 'string', 'max:255'],
                        'slug' => ['sometimes', 'string', 'max:255', 'unique:posts,slug'],
                        'excerpt' => ['nullable', 'string', 'max:500'],
                        'content' => ['sometimes', 'string'],
                        'category_id' => ['nullable', 'exists:categories,id'],
                        'featured_image' => ['nullable', 'string', 'url', 'max:500'],
                        'tags' => ['nullable', 'array'],
                        'tags.*' => ['exists:tags,id'],
                    ])
                    ->openapiRequest([
                        'title' => fake()->sentence(),
                        'slug' => fake()->slug(),
                        'excerpt' => fake()->paragraph(),
                        'content' => fake()->paragraphs(3, true),
                        'category_id' => fake()->randomNumber(),
                        'featured_image' => fake()->imageUrl(),
                        'tags' => [fake()->randomNumber(), fake()->randomNumber()],
                    ])
                    ->policy('update')
                    ->handle(function ($request, $model, $payload) {
                        return app(PostService::class)->update($model, $payload);
                    })
                )
                ->delete(fn ($action) => $action
                    ->policy('delete')
                )

                // Custom Actions
                ->use(PublishPost::class)
                ->use(UnpublishPost::class)
                ->use(ArchivePost::class)
                ->use(FeaturePost::class)
                ->use(UnfeaturePost::class)
                ->use(DuplicatePost::class)
                ->use(BulkPublishPosts::class)
            );
    }
}
