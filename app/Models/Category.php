<?php

namespace App\Models;

use BehindSolution\LaravelQueryGate\Support\QueryGate;
use BehindSolution\LaravelQueryGate\Traits\HasQueryGate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasQueryGate;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public static function queryGate(): QueryGate
    {
        return QueryGate::make()
            ->alias('categories')
            ->cache(300, 'categories-list')
            ->openapiResponse([
                'id' => fake()->randomNumber(),
                'name' => fake()->word(),
                'slug' => fake()->slug(),
                'description' => fake()->sentence(),
                'is_active' => fake()->boolean(),
                'created_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
            ])
            ->filters([
                'name' => ['string', 'max:100'],
                'slug' => ['string', 'max:100'],
                'is_active' => 'boolean',
                'created_at' => 'date',
            ])
            ->allowedFilters([
                'name' => ['eq', 'like'],
                'slug' => ['eq'],
                'is_active' => ['eq'],
                'created_at' => ['gte', 'lte', 'between'],
            ])
            ->select(['id', 'name', 'slug', 'description', 'is_active', 'created_at'])
            ->sorts(['name', 'created_at'])
            ->actions(fn ($actions) => $actions
                ->create(fn ($action) => $action
                    ->validations([
                        'name' => ['required', 'string', 'max:100'],
                        'slug' => ['required', 'string', 'max:100', 'unique:categories,slug'],
                        'description' => ['nullable', 'string', 'max:500'],
                        'is_active' => ['boolean'],
                    ])
                    ->openapiRequest([
                        'name' => fake()->word(),
                        'slug' => fake()->slug(),
                        'description' => fake()->sentence(),
                        'is_active' => fake()->boolean(),
                    ])
                )
                ->update(fn ($action) => $action
                    ->validations([
                        'name' => ['sometimes', 'string', 'max:100'],
                        'slug' => ['sometimes', 'string', 'max:100', 'unique:categories,slug'],
                        'description' => ['nullable', 'string', 'max:500'],
                        'is_active' => ['boolean'],
                    ])
                    ->openapiRequest([
                        'name' => fake()->word(),
                        'slug' => fake()->slug(),
                        'description' => fake()->sentence(),
                        'is_active' => fake()->boolean(),
                    ])
                )
                ->delete()
            );
    }
}
