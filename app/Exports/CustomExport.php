<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class CustomExport implements FromView
{
    public function __construct(
        private Collection $items,
        private Collection $instances,
        private bool $repeatable
    ) {}

    public function view(): View
    {
        return view('exports.custom', [
            'items' => $this->items,
            'instances' => $this->instances,
            'repeatable' => $this->repeatable,
        ]);
    }
}
