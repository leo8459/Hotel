<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReporteTurno extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfContent;
    public $nombreArchivo;

    public function __construct($pdfContent, $nombreArchivo)
    {
        $this->pdfContent = $pdfContent;
        $this->nombreArchivo = $nombreArchivo;
    }

    public function build()
    {
        return $this->subject('Reporte de Turno')
            ->view('emails.reporte_turno') // Una vista simple de cuerpo
            ->attachData($this->pdfContent, $this->nombreArchivo, [
                'mime' => 'application/pdf',
            ]);
    }
}
