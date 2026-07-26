<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'source' => $this->source,
            'total_visits' => $this->total_visits,
            'last_connected' => $this->last_connected,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'created_at' => $this->created_at,
        ];
    }
}
