<?php

namespace App\Mail;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RentalConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $rental;

    public function __construct(Rental $rental)
    {
        $this->rental = $rental;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'E-Ticket Hiko App - Pesanan ' . $this->rental->rental_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rental_confirmed',
        );
    }

    public function attachments(): array
    {
        // Generate QR Code as base64
        $qrCode = base64_encode(QrCode::format('png')->size(300)->margin(1)->generate($this->rental->rental_code));

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', [
            'rental' => $this->rental,
            'qrCode' => $qrCode
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'E-Ticket-' . $this->rental->rental_code . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
