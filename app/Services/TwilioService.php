<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $twilio;
    protected $client;
    protected $from;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->from = config('services.twilio.whatsapp_from');
    }

    public function sendSms($to, $message)
    {
        return $this->twilio->messages->create(
            $to, // Ej: 'whatsapp:+521234567890'
            [
                'from' => $this->from,
                'body' => $message
            ]
        );
    }
}
