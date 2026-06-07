<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWordPressSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'site_url' => ['required', 'url', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'application_password' => ['required', 'string', 'max:255'],
        ];
    }
}
