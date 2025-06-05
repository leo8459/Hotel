<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Alquiler;

class BoletaAlquiler extends Mailable
{
    use Queueable, SerializesModels;

    public Alquiler $alquiler;
    protected string $pdfContent;

    /**
     * @param  Alquiler $alquiler  El registro que acabas de cobrar
     * @param  string   $pdfContent Contenido binario del PDF
     */
    public function __construct(Alquiler $alquiler, string $pdfContent)
    {
        $this->alquiler    = $alquiler;
        $this->pdfContent  = $pdfContent;
    }

    public function build()
    {
        return $this->subject('Boleta de alquiler #'.$this->alquiler->id)
                    ->markdown('emails.boleta')          // Un cuerpo sencillo tipo «Su boleta está adjunta…»
                    ->attachData(
                        $this->pdfContent,
                        "boleta_{$this->alquiler->id}.pdf",
                        ['mime' => 'application/pdf']
                    );
    }
}
