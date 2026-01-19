<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserResource;
use BehindSolution\LaravelQueryGate\Contracts\QueryGateAction;
use BehindSolution\LaravelQueryGate\Support\ActionDefinition;
use BehindSolution\LaravelQueryGate\Support\ActionsBuilder;
use BehindSolution\LaravelQueryGate\Support\QueryGate;
use BehindSolution\LaravelQueryGate\Traits\HasQueryGate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    use HasQueryGate;

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function queryGate(): QueryGate
    {
        return QueryGate::make()
            ->alias('users')
            ->openapiResponse([
                'id' => fake()->randomNumber(),
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
            ])
            ->select(['id', 'name', 'email'])
            ->actions(fn ($actions) => $actions
                ->create(fn (ActionDefinition $action) => $action
                    ->validations([
                        'name' => ['required', 'string'],
                        'email' => ['required', 'email', 'unique:users,email'],
                        'password' => ['required', 'string'],
                    ])
                    ->openapiRequest([
                        'name' => fake()->name(),
                        'email' => fake()->safeEmail(),
                        'password' => fake()->password(),
                    ])
                    ->handle(function ($request, $model, $payload) {
                        $model->fill($payload);
                        $model->password = Hash::make($payload['password']);
                        $model->save();

                        return new UserResource($model);
                    })
                )
                ->update(fn ($action) => $action
                    ->validations([
                        'name' => ['required', 'string'],
                    ])
                    ->openapiRequest([
                        'name' => fake()->name(),
                    ])
                )
                ->delete()
                ->detail(fn ($action) => $action
                    ->select(UserDetailResource::class)
                    ->query(fn ($query) => $query->withCount(['posts', 'comments']))
                )
            );
    }
}
