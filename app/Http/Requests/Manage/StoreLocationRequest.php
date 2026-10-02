<?php

declare(strict_types=1);

namespace App\Http\Requests\Manage;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
        ];
    }
}
