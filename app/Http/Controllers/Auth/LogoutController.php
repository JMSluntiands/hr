<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LogoutController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        session()->flush();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }
}
