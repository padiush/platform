<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Database\Eloquent\Collection;

class CustomExport implements FromView
{
    public function __construct(Collection $items, Collection $instances, bool $repeatable)
    {
        $this->items = $items;
        $this->instances = $instances;
        $this->repeatable = $repeatable;
    }

    public function view(): View
    {
        return view('exports.custom', [
            'items' => $this->items,
            'instances' => $this->instances,
            'repeatable' => $this->repeatable
        ]);
    }
}