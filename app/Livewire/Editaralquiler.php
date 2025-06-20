<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Alquiler;

class Editaralquiler extends Component
{
    public $alquiler;

    public function mount($alquiler)
    {
        $this->alquiler = Alquiler::findOrFail($alquiler);
    }

  public function render()
{
    return view('livewire.editaralquiler')
        ->extends('adminlte::page') // O usa el layout que estés usando
        ->section('content');
}

}
