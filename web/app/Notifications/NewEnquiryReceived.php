<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lead;
use App\Support\Contact\PhoneNumber;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An enquiry is only worth capturing if somebody reads it the same day. The lead
 * is stored regardless; this mail is what turns a form submission into a reply
 * for a studio that does not sit in the admin console all afternoon.
 */
final class NewEnquiryReceived extends Notification
{
    public function __construct(private readonly Lead $lead) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->lead;
        // Timestamps are stored in UTC; the studio reads them in WIB.
        $receivedAt = ($lead->created_at ?? now())->copy()->setTimezone('Asia/Jakarta');
        $whatsappDigits = filled($lead->whatsapp) ? PhoneNumber::international((string) $lead->whatsapp) : null;

        $rows = [
            ['label' => 'Nama', 'value' => (string) $lead->name],
            ['label' => 'Email', 'value' => (string) $lead->email, 'href' => 'mailto:'.$lead->email],
        ];

        if (filled($lead->whatsapp)) {
            $rows[] = [
                'label' => 'WhatsApp',
                'value' => PhoneNumber::display((string) $lead->whatsapp),
                'href' => $whatsappDigits !== null ? 'https://wa.me/'.$whatsappDigits : null,
            ];
        }

        $interest = $this->lead->serviceInterestLabel();

        if ($interest !== null) {
            $rows[] = ['label' => 'Layanan ditanyakan', 'value' => $interest];
        }

        $rows[] = ['label' => 'Sumber', 'value' => $this->sourceLabel()];

        return (new MailMessage)
            ->subject('Enquiry baru — '.$lead->name)
            ->view(
                ['html' => 'mail.notifications.new-enquiry', 'text' => 'mail.notifications.new-enquiry-text'],
                [
                    'name' => (string) $lead->name,
                    'rows' => $rows,
                    // Not "message": Laravel injects the Illuminate\Mail\Message
                    // under that key before the view renders.
                    'enquiryMessage' => filled($lead->message) ? (string) $lead->message : null,
                    'replyUrl' => 'mailto:'.(string) $lead->email.'?subject='.rawurlencode('Re: enquiry '.config('app.name')),
                    'whatsappUrl' => $whatsappDigits !== null ? 'https://wa.me/'.$whatsappDigits : null,
                    'receivedAt' => $receivedAt->locale(app()->getLocale())->translatedFormat('l, d F Y · H:i').' WIB',
                    'source' => $this->sourceLabel(),
                ],
            );
    }

    /**
     * Human label for the form the visitor used; the column itself holds a slug.
     */
    private function sourceLabel(): string
    {
        return match ((string) $this->lead->source) {
            'landing' => 'formulir halaman utama',
            '' => 'halaman kontak',
            default => str_replace('_', ' ', (string) $this->lead->source),
        };
    }
}
