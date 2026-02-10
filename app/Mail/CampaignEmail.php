<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Models\Campaign;
use App\Models\Subscriber;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $body = '';
    public string $unsubscribe_url = '';

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Campaign $campaign,
        public Subscriber $subscriber
    )
    {
        $this->subject = $this->campaign->subject;
        $this->body = $this->campaign->body;
        $TOKENS = ['name', 'email'];
        foreach ($TOKENS as $token) {
            $this->subject = str_replace("{{" . $token . "}}", $this->subscriber->{$token}, $this->subject);
            $this->body = str_replace("{{" . $token . "}}", $this->subscriber->{$token}, $this->body);
        }

        $this->unsubscribe_url = route('unsubscribe', [
            'token' => $subscriber->unsubscribe_token,
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'mail.campaign',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
