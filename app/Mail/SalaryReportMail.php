<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalaryReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $period,
        private readonly string $depot,
        private readonly string $role,
        private readonly string $pdfContent,
        private readonly string $fileName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Salary Report - ' . $this->period,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.salary-report',
            with: [
                'period' => $this->period,
                'depot' => $this->depot,
                'role' => $this->role,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->fileName)
                ->withMime('application/pdf'),
        ];
    }
}
