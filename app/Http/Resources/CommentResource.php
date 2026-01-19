<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            'content' => $this->content,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'post' => $this->whenLoaded('post', fn () => [
                'id' => $this->post->id,
                'title' => $this->post->title,
            ]),
        ];
    }
}
