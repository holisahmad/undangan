<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $weddings = $request->user()
            ->weddings()
            ->with(['activePublication', 'partners', 'events'])
            ->latest()
            ->paginate(9);

        return view('dashboard', [
            'weddings' => $weddings,
        ]);
    }
}
