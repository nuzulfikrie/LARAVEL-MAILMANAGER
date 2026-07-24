<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplateVersion;

/**
 * @extends Factory<EmailTemplateVersion>
 */
class EmailTemplateVersionFactory extends Factory
{
    protected $model = EmailTemplateVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subject = 'Hello {name}';
        $html = '<p>Hello {name}</p>';
        $design = ['body' => ['rows' => []]];
        $parameters = [
            'name' => [
                'type' => 'string',
                'required' => true,
            ],
        ];

        return [
            'email_template_id' => EmailTemplate::factory(),
            'version' => 1,
            'content_hash' => hash('sha256', $subject.$html.json_encode($design).json_encode($parameters)),
            'subject' => $subject,
            'design_json' => $design,
            'html_content' => $html,
            'parameters' => $parameters,
            'created_by' => null,
        ];
    }
}
