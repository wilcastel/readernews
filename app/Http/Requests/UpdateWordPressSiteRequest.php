<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWordPressSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'site_url' => ['sometimes', 'url', 'max:255', Rule::unique('wordpress_sites', 'site_url')->where('user_id', auth()->id())->ignore($this->wordPressSite)],
            'username' => ['sometimes', 'string', 'max:255'],
            'application_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
