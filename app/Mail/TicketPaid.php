<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketPaid extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket_number;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticket_number)
    {
        $this->ticket_number = $ticket_number;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown("emails.tickets.paid")->with([
            "ticket_number" => $this->ticket_number,
        ]);
    }
}
