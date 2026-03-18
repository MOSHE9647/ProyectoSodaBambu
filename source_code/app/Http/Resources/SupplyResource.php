<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $createdAt = $this->created_at;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'measure_unit' => $this->measure_unit,
            'created_at' => $createdAt instanceof \DateTimeInterface
                ? $createdAt->format('Y-m-d H:i:s')
                : (is_string($createdAt) ? $createdAt : null),
        ];
    }
}