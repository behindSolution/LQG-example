<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'parent_id' => $this->parent_id,
            'author_name' => $this->author_name,
            'author_email' => $this->author_email,
            'content' => $this->content,
            'status' => $this->status,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'post' => $this->whenLoaded('post', fn () => [
                'id' => $this->post->id,
                'title' => $this->post->title,
                'slug' => $this->post->slug,
                'status' => $this->post->status,
            ]),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'email' => $this->author->email,
            ]),
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->id,
                'author_name' => $this->parent->author_name,
                'content' => $this->parent->content,
            ]),
            'replies_count' => $this->whenCounted('replies'),
        ];
    }
}
