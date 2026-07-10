<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class EthnobotanyRExport implements FromView
{
    public function __construct(
        private $answers,
        private $categories
    ) {}

    public function view(): View
    {
        return view('exports.ethnobotanyr', [
            'answers' => $this->answers,
            'categories' => $this->categories,
        ]);
    }
}
