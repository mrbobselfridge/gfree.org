<?php

namespace App\Mail;

use App\Models\WorkflowNotificationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkflowNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WorkflowNotificationEvent $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->event->rule->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.workflow-notification',
        );
    }
}
