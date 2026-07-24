<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mailer' => ['nullable', 'string', 'max:64'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', Rule::in(['none', 'tls', 'ssl', 'starttls', 'smtps'])],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'delivery_enabled' => ['nullable', 'boolean'],
            'redirect_to' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsData(): array
    {
        $data = $this->validated();
        $data['delivery_enabled'] = $this->boolean('delivery_enabled');

        return $data;
    }
}
