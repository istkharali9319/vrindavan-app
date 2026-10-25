<?php

namespace App\Console\Commands;

use App\Support\MailCheck;
use Illuminate\Console\Command;

class SendTestEmail extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mail:test {email : Where to send the test email}';

    /**
     * @var string
     */
    protected $description = 'Sends a short test email using the mail settings in .env';

    public function handle(): int
    {
        $error = MailCheck::send((string) $this->argument('email'));

        if ($error !== null) {
            $this->components->error('Sending failed: '.$error);

            return self::FAILURE;
        }

        $this->components->info('Sent. Check the inbox (and the spam folder) of '.$this->argument('email').'.');

        return self::SUCCESS;
    }
}
