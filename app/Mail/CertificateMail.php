<?php

/**
 * app/Mail/CertificateMail.php
 * Email distribusi sertifikat dengan lampiran PDF + petunjuk verifikasi (FR-22).
 */

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Certificate $certificate) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sertifikat Anda — '.$this->certificate->registration->event->nama);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.certificate', with: [
            'nama' => $this->certificate->registration->person->nama,
            'acara' => $this->certificate->registration->event->nama,
            'nomor' => $this->certificate->nomor_unik,
            'verifyUrl' => url('/verify/'.$this->certificate->qr_token),
        ]);
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(config('sigital.pdf_disk'), $this->certificate->pdf_path)
                ->as('sertifikat-'.$this->certificate->id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
