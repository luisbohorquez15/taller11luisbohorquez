<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendGridService
{
    /**
     * Envía un correo utilizando la API de SendGrid o Mailpit si falla/falta la API Key.
     */
    public function sendEmail(string $to, string $subject, string $content): bool
    {
        $apiKey = config('services.sendgrid.key');

        // Si la clave no es de SendGrid (no empieza por SG.), usa Mailpit/SMTP local
        if (!$apiKey || !str_starts_with($apiKey, 'SG.')) {
            try {
                Mail::html($content, function ($message) use ($to, $subject) {
                    $message->to($to)
                        ->subject($subject)
                        ->from(config('mail.from.address', 'fmoreno4@unab.edu.co'), config('mail.from.name', 'CloudSpace Solutions'));
                });
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        // Si hay una API Key válida de SendGrid, realiza la petición HTTP v3
        $response = Http::withToken($apiKey)
            ->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [
                    [
                        'to' => [
                            ['email' => $to]
                        ]
                    ]
                ],
                'from' => [
                    'email' => config('mail.from.address', 'fmoreno4@unab.edu.co'),
                    'name' => config('mail.from.name', 'CloudSpace Solutions')
                ],
                'subject' => $subject,
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $content
                    ]
                ]
            ]);

        return $response->successful();
    }
}