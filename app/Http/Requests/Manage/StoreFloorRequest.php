<?php

declare(strict_types=1);

namespace App\Http\Requests\Manage;

use Illuminate\Foundation\Http\FormRequest;

class StoreFloorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'level' => ['required', 'integer', 'between:-10,200'],
        ];
    }
}
