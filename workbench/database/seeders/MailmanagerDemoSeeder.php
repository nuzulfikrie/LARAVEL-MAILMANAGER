<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;

class MailmanagerDemoSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SettingsRepository $settings */
        $settings = app(SettingsRepository::class);
        $settings->putMany('mail', [
            'mailer' => 'array',
            'host' => '127.0.0.1',
            'port' => 1025,
            'encryption' => 'none',
            'delivery_enabled' => true,
            'from_address' => 'noreply@mailmanager.test',
            'from_name' => 'Mailmanager Demo',
        ]);

        /** @var EmailTemplateService $templates */
        $templates = app(EmailTemplateService::class);

        $welcome = $templates->create([
            'name' => 'User Welcome',
            'slug' => 'user-welcome',
            'subject' => 'Welcome {name}',
            'html_content' => '<p>Hello {name}, welcome aboard.</p>',
            'parameters' => [
                'name' => ['type' => 'string', 'required' => true],
            ],
            'description' => 'Demo welcome template',
        ]);
        $templates->activate($welcome);

        $invoiceHtml = <<<'HTML'
<p>Hello {customer_name},</p>
<p>Invoice {invoice_number}:</p>
<table data-email-collection="invoice_items">
  <thead><tr><th>Description</th><th>Qty</th><th>Total</th></tr></thead>
  <tbody>
    <tr data-email-row-template>
      <td>{description}</td>
      <td>{quantity}</td>
      <td>{total}</td>
    </tr>
  </tbody>
</table>
HTML;

        $invoice = $templates->create([
            'name' => 'Invoice Ready',
            'slug' => 'invoice-ready',
            'subject' => 'Invoice {invoice_number}',
            'html_content' => $invoiceHtml,
            'parameters' => [
                'customer_name' => ['type' => 'string', 'required' => true],
                'invoice_number' => ['type' => 'string', 'required' => true],
                'invoice_items' => [
                    'type' => 'collection',
                    'required' => true,
                    'columns' => [
                        ['field' => 'description', 'format' => 'plain'],
                        ['field' => 'quantity', 'format' => 'integer'],
                        ['field' => 'total', 'format' => 'currency', 'currency' => 'MYR'],
                    ],
                ],
            ],
        ]);
        $templates->activate($invoice);
    }
}
