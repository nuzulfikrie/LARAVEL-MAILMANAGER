<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Console\Commands;

use Illuminate\Console\Command;
use NuzulFikrieCoder\LaravelMailmanager\Services\SmtpProbeService;
use Throwable;

class TestSmtpCommand extends Command
{
    protected $signature = 'mailmanager:smtp-test {email : Recipient address for the probe message}';

    protected $description = 'Send a test message using package SMTP settings';

    public function handle(SmtpProbeService $probe): int
    {
        $email = is_string($this->argument('email')) ? $this->argument('email') : '';

        try {
            $probe->sendTest($email);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("SMTP probe sent to [{$email}].");

        return self::SUCCESS;
    }
}
