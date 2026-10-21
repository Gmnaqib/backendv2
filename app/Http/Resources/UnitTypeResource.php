<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitTypeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->nama,
            'unit_id' => $this->id,

            'children' => UnitTypeResource::collection(
                $this->whenLoaded('childrenRecursive')
            )
        ];
    }
}
