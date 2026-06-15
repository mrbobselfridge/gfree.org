<?php

namespace App\Mail;

use App\Models\WorkflowNotificationEvent;
use App\Support\WorkflowNotificationTemplate;
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
            subject: WorkflowNotificationTemplate::render($this->event->rule->subject, $this->event),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.workflow-notification',
        );
    }
}
