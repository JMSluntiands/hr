<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\LegacyPhpRenderer;
use Illuminate\Http\Response;

class LegacyPageController extends Controller
{
    public function __construct(private LegacyPhpRenderer $legacy) {}

    public function compensation(): Response
    {
        return $this->legacy->renderEmployee('compensation.php');
    }

    public function settings(): Response
    {
        return $this->legacy->renderEmployee('settings.php');
    }

    public function inventory(): Response
    {
        return $this->legacy->renderEmployee('inventory.php');
    }

    public function progressiveDiscipline(): Response
    {
        return $this->legacy->renderEmployee('progressive-discipline.php');
    }

    public function performance(): Response
    {
        return $this->legacy->renderEmployee('performance.php');
    }

    public function performanceMyReviews(): Response
    {
        return $this->legacy->renderEmployee('performance-my-reviews.php');
    }

    public function performanceFormReview(): Response
    {
        return $this->legacy->renderEmployee('performance-form-review.php');
    }

    public function performanceReviewSubmissions(): Response
    {
        return $this->legacy->renderEmployee('performance-review-submissions.php');
    }

    public function incidentReports(): Response
    {
        return $this->legacy->renderEmployee('incident-report-list.php');
    }

    public function incidentReportCreate(): Response
    {
        return $this->legacy->renderEmployee('incident-report-add.php');
    }
}
