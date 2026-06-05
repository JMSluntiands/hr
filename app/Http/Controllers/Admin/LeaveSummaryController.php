<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeaveSummaryService;
use Illuminate\View\View;

class LeaveSummaryController extends Controller
{
    public function __construct(
        private readonly LeaveSummaryService $summaryService,
    ) {}

    public function index(): View
    {
        $year = (int) date('Y');

        return view('admin.leaves.summary', [
            'year' => $year,
            'summaries' => $this->summaryService->forYear($year),
        ]);
    }
}
