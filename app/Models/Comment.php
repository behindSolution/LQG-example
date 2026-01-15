<?php

namespace App\Models;

use App\Actions\QueryGate\Comments\ApproveComment;
use App\Actions\QueryGate\Comments\MarkAsSpam;
use App\Actions\QueryGate\Comments\RejectComment;
use BehindSolution\LaravelQueryGate\Support\QueryGate;
use BehindSolution\LaravelQueryGate\Traits\HasQueryGate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, HasQueryGate, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SPAM = 'spam';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'author_name',
        'author_email',
        'content',
        'status',
        'ip_address',
    ];

    // Relationships
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // Scopes
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRootLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // Helpers
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getAuthorDisplayName(): string
    {
        return $this->author?->name ?? $this->author_name ?? 'Anonymous';
    }

    public static function queryGate(): QueryGate
    {
        return QueryGate::make()
            ->alias('comments')
            ->cache(60, 'comments-list')
            ->paginationMode('classic')
            ->query(fn ($query, $request) => $query->with([
                'post:id,title,slug',
                'author:id,name',
                'parent:id,content',
            ]))

            ->filters([
                'post_id' => 'integer',
                'user_id' => 'integer',
                'parent_id' => 'integer',
                'status' => ['string', 'in:pending,approved,spam,rejected'],
                'author_name' => ['string', 'max:100'],
                'author_email' => ['string', 'email'],
                'content' => ['string', 'max:255'],
                'created_at' => 'date',
                'post.title' => ['string', 'max:255'],
                'post.status' => ['string', 'in:draft,pending,published,archived'],
                'author.name' => ['string', 'max:100'],
            ])
            ->allowedFilters([
                'post_id' => ['eq', 'in'],
                'user_id' => ['eq', 'in'],
                'parent_id' => ['eq'],
                'status' => ['eq', 'in', 'neq'],
                'author_name' => ['eq', 'like'],
                'author_email' => ['eq'],
                'content' => ['like'],
                'created_at' => ['eq', 'gte', 'lte', 'between'],
                'post.title' => ['like'],
                'post.status' => ['eq'],
                'author.name' => ['like'],
            ])
            ->rawFilters([
                'content' => fn ($builder, $operator, $value, $column) =>
                    $builder->where($column, 'like', '%' . $value . '%'),
            ])
            ->select([
                'id', 'post_id', 'user_id', 'parent_id',
                'author_name', 'author_email', 'content',
                'status', 'created_at',
                'post.id', 'post.title', 'post.slug',
                'author.id', 'author.name',
            ])
            ->sorts(['created_at', 'status'])

            ->actions(fn ($actions) => $actions
                ->create(fn ($action) => $action
                    ->validations([
                        'post_id' => ['required', 'exists:posts,id'],
                        'parent_id' => ['nullable', 'exists:comments,id'],
                        'author_name' => ['required_without:user_id', 'nullable', 'string', 'max:100'],
                        'author_email' => ['required_without:user_id', 'nullable', 'email', 'max:255'],
                        'content' => ['required', 'string', 'max:2000'],
                    ])
                    ->handle(function ($request, $model, $payload) {
                        $model->fill($payload);
                        $model->user_id = $request->user()?->id;
                        $model->ip_address = $request->ip();
                        $model->status = $request->user() ? self::STATUS_APPROVED : self::STATUS_PENDING;
                        $model->save();

                        return $model->load(['post:id,title,slug', 'author:id,name']);
                    })
                )
                ->update(fn ($action) => $action
                    ->validations([
                        'content' => ['sometimes', 'string', 'max:2000'],
                    ])
                    ->policy('update')
                )
                ->delete(fn ($action) => $action
                    ->policy('delete')
                )

                // Custom Actions
                ->use(ApproveComment::class)
                ->use(RejectComment::class)
                ->use(MarkAsSpam::class)
            );
    }
}
