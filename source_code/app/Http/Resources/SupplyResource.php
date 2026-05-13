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
            'measure_unit' => $this->measure_unit?->value,
            'measure_unit_label' => $this->measure_unit?->label(),
            'measure_amount' => $this->measure_amount,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'total_measure_amount' => $this->quantity * (float) $this->measure_amount,
            'created_at' => $createdAt instanceof \DateTimeInterface
                ? $createdAt->format('Y-m-d H:i:s')
                : (is_string($createdAt) ? $createdAt : null),
        ];
    }
}
