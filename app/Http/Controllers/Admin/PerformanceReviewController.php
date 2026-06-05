<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceReviewService;
use Illuminate\View\View;

class PerformanceReviewController extends Controller
{
    public function __construct(
        private PerformanceReviewService $performanceReviews,
    ) {}

    public function index(): View
    {
        $rows = $this->performanceReviews->listForAdmin();

        return view('admin.performance-reviews.index', [
            'reviews' => $rows,
            'performanceReviews' => $this->performanceReviews,
        ]);
    }
}
