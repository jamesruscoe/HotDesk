<?php

declare(strict_types=1);

namespace App\Http\Requests\Manage;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'timezone:all'],
        ];
    }
}
