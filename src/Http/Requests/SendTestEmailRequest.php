<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTestEmailRequest extends FormRequest
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
            'to' => ['required', 'email'],
            'parameters' => ['nullable'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parameters(): array
    {
        $parameters = $this->input('parameters', []);

        if (is_string($parameters)) {
            $decoded = json_decode($parameters, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($parameters) ? $parameters : [];
    }
}
