<?php

namespace App\Mail;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para notificaciones de formularios a administradores y usuarios.
 *
 * Entrega directa e instantánea a través del transporte SMTP.
 */
class FormNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Form $form,
        public readonly FormSubmission $submission,
        public readonly string $subjectText,
        public readonly string $htmlBody,
        public readonly bool $isAutoReply = false,
    ) {}

    /**
     * Definición del sobre del email (remitente, destinatario, asunto).
     */
    public function envelope(): Envelope
    {
        $siteSettings = SiteSetting::cached();
        $siteName = $siteSettings->site_name ?? config('app.name', 'CMS');
        $fromEmail = config('mail.from.address', 'info@desarrollos.eu');

        return new Envelope(
            from: new Address($fromEmail, $siteName),
            subject: $this->subjectText,
        );
    }

    /**
     * Definición del contenido del email con vista blade y datos inyectados.
     */
    public function content(): Content
    {
        $siteSettings = SiteSetting::cached();
        $siteName = $siteSettings->site_name ?? config('app.name', 'CMS');

        return new Content(
            view: 'emails.form-notification',
            with: [
                'form'              => $this->form,
                'submission'        => $this->submission,
                'subjectText'       => $this->subjectText,
                'htmlBody'          => $this->htmlBody,
                'siteName'          => $siteName,
                'isAutoReply'       => $this->isAutoReply,
                'emailHeaderColor'  => $this->form->settings['email_header_color'] ?? '059669',
                'emailLogoUrl'      => $this->form->settings['email_logo_url'] ?? ($siteSettings->logo_path ?? ''),
            ],
        );
    }
}
