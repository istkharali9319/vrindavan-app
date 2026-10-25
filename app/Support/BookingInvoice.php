<?php

namespace App\Support;

use App\Models\Booking;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

/**
 * The PDF invoice for a booking. It is built from the booking itself, so a booking always has exactly
 * one invoice number and the same content, however often it is downloaded or emailed.
 */
class BookingInvoice
{
    public function __construct(private Booking $booking) {}

    public static function for(Booking $booking): self
    {
        return new self($booking->loadMissing(['roomType', 'room', 'addOns']));
    }

    /**
     * e.g. "INV-VRN-7K3QX9PD" — one per booking reference.
     */
    public function number(): string
    {
        return 'INV-'.$this->booking->reference;
    }

    public function filename(): string
    {
        return $this->number().'.pdf';
    }

    public function html(): string
    {
        $site = app(WebsiteContent::class)->for('site');

        return view('pdf.invoice', [
            'booking' => $this->booking,
            'invoiceNumber' => $this->number(),
            'issuedOn' => $this->booking->created_at->timezone(config('booking.timezone')),
            'business' => [
                'name' => $site->text('invoice.legal_name') ?: config('resort.name'),
                'gstin' => $site->text('invoice.gstin'),
                'note' => $site->text('invoice.note'),
                'address' => config('resort.address'),
                'email' => config('resort.email'),
                'phone' => config('resort.phones.0.display'),
            ],
        ])->render();
    }

    public function download(): Response
    {
        return response($this->pdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename().'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function pdf(): string
    {
        $options = new Options;
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(false);
        $options->setChroot(public_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
