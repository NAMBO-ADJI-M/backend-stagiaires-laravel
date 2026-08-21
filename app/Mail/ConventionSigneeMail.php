<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class ConventionSigneeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $autorisation;
    public $entreprise;
    public $stagiaire;

    public function __construct($autorisation)
    {
        $this->autorisation = $autorisation;
        $this->entreprise = $autorisation->entreprise;
        $this->stagiaire = $autorisation->stagiaire;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📜 Convention de Stage Signée - StageLink',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.convention_signee',
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.convention', [
            'autorisation' => $this->autorisation,
            'entreprise' => $this->entreprise,
            'stagiaire' => $this->stagiaire,
            'logo_url' => $this->entreprise->photo_profil_url,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'convention-stage.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
