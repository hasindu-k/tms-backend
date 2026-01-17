<?php

namespace App\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;

class BrevoEmailService
{
    protected TransactionalEmailsApi $api;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', config('services.brevo.key'));

        $this->api = new TransactionalEmailsApi(null, $config);
    }

    public function send(
        string $toEmail,
        string $subject,
        string $htmlContent
    ) {
        return $this->api->sendTransacEmail([
            'subject' => $subject,
            'sender' => [
                'email' => config('services.brevo.sender_email'),
                'name'  => config('services.brevo.sender_name'),
            ],
            'to' => [
                ['email' => $toEmail]
            ],
            'htmlContent' => $htmlContent,
        ]);
    }
}
