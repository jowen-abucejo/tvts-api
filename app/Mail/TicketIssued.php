<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketIssued extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket_number;
    public $qr;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticket_number, $qr)
    {
        $this->ticket_number = $ticket_number;
        $this->qr = $qr;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.tickets.issued')->with(['ticket_number' => $this->ticket_number])->attachFromStorage($this->qr);
    }
}
