<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Headers;
use Symfony\Component\Mime\Email;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset OTP',
            using: [
                function (Email $message) {
                    $message->text(
                        "Your password reset OTP is: {$this->otp}\n\n"
                            . "This code will expire in 5 minutes."
                    );
                },
            ],
        );
    }
}
