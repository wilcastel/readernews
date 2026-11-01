<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'site_url' => ['required', 'url', 'max:255', Rule::unique('wordpress_sites', 'site_url')->where('user_id', auth()->id())],
            'username' => ['required', 'string', 'max:255'],
            'application_password' => ['required', 'string', 'max:255'],
        ];
    }
}
