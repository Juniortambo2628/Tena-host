<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'wifi_ssid' => $this->wifi_ssid,
            'occupancy_threshold' => $this->occupancy_threshold,
            'pms_integration_type' => $this->pms_integration_type,
            'pms_connection_status' => $this->pms_connection_status,
            'guests_count' => $this->whenCounted('guests'),
            'access_points_count' => $this->whenCounted('accessPoints'),
            'access_points' => AccessPointResource::collection($this->whenLoaded('accessPoints')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
