<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;

class UpdateTemplateRequest extends FormRequest
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
        $table = config('laravel-mailmanager.tables.templates', 'email_templates');
        /** @var EmailTemplate $template */
        $template = $this->route('template');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')->ignore($template->id)],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique($table, 'slug')->ignore($template->id)],
            'description' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:998'],
            'html_content' => ['required', 'string'],
            'design_json' => ['nullable'],
            'parameters' => ['nullable'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $data = $this->validated();

        if (isset($data['design_json']) && is_string($data['design_json'])) {
            $decoded = json_decode($data['design_json'], true);
            $data['design_json'] = is_array($decoded) ? $decoded : [];
        }

        if (isset($data['parameters']) && is_string($data['parameters'])) {
            $decoded = json_decode($data['parameters'], true);
            $data['parameters'] = is_array($decoded) ? $decoded : [];
        }

        return $data;
    }
}
