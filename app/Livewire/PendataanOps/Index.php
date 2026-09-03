<?php

namespace App\Livewire\PendataanOps;

use App\Models\PendataanOps;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kerangka awal menu Pendataan OPS.
 * Field & aksi detail akan ditambahkan sesuai instruksi berikutnya.
 */
#[Layout('layouts.app')]
#[Title('Pendataan OPS')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.pendataan-ops.index', [
            'data' => PendataanOps::latest()->paginate(10),
        ]);
    }
}
