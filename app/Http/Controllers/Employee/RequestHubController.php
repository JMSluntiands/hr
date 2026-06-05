<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\LegacyPhpRenderer;
use Illuminate\Http\Response;

class RequestHubController extends Controller
{
    public function __construct(private LegacyPhpRenderer $legacy) {}

    public function index(): Response
    {
        return $this->legacy->renderEmployee('request.php');
    }
}
