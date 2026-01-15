<?php

namespace App\Models;

use BehindSolution\LaravelQueryGate\Support\QueryGate;
use BehindSolution\LaravelQueryGate\Traits\HasQueryGate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory, HasQueryGate;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public static function queryGate(): QueryGate
    {
        return QueryGate::make()
            ->alias('tags')
            ->cache(600, 'tags-list')
            ->filters([
                'name' => ['string', 'max:50'],
                'slug' => ['string', 'max:50'],
            ])
            ->allowedFilters([
                'name' => ['eq', 'like'],
                'slug' => ['eq'],
            ])
            ->select(['id', 'name', 'slug'])
            ->sorts(['name', 'created_at'])
            ->actions(fn ($actions) => $actions
                ->create(fn ($action) => $action
                    ->validations([
                        'name' => ['required', 'string', 'max:50'],
                        'slug' => ['required', 'string', 'max:50', 'unique:tags,slug'],
                    ])
                )
                ->update(fn ($action) => $action
                    ->validations([
                        'name' => ['sometimes', 'string', 'max:50'],
                        'slug' => ['sometimes', 'string', 'max:50', 'unique:tags,slug'],
                    ])
                )
                ->delete()
            );
    }
}
