<?php

namespace App\Http\Controllers;

use App\Admin\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private AdminDashboardService $dashboard) {}

    public function __invoke(): Response
    {
        return Inertia::render('dashboard', $this->dashboard->summary());
    }
}
