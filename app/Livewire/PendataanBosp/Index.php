<?php

namespace App\Livewire\PendataanBosp;

use App\Models\PendataanBosp;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kerangka awal menu Pendataan BOSP.
 * Field & aksi detail akan ditambahkan sesuai instruksi berikutnya.
 */
#[Layout('layouts.app')]
#[Title('Pendataan BOSP')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.pendataan-bosp.index', [
            'data' => PendataanBosp::latest()->paginate(10),
        ]);
    }
}
