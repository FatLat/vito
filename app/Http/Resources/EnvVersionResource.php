<?php

namespace App\Http\Resources;

use App\Models\EnvVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnvVersion */
class EnvVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'path' => $this->path,
            'user' => $this->user?->name,
            'created_at' => $this->created_at,
        ];
    }
}
