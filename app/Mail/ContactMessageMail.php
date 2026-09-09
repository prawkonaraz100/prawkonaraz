<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array{name: string, email: string, topic: string, message: string}  $messageData
     */
    public function __construct(public readonly array $messageData) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->messageData['email'], $this->messageData['name'])],
            subject: 'Wiadomość ze strony: '.$this->messageData['topic'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
