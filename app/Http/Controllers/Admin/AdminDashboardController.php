<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Will load dashboard view with analytics, reservations, inventory, etc.
        return view('admin.dashboard');
    }
}
