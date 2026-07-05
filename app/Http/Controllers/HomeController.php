<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\Sparepart;
use Carbon\Carbon;
use App\Models\Service;
use App\Models\ServiceHistory;
use App\Models\ServiceDetail;
use Illuminate\Support\Facades\DB;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'today');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($filter == 'today') {
            $start = Carbon::today();
            $end = Carbon::today()->endOfDay();
        } elseif ($filter == 'week') {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        } elseif ($filter == 'month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            $start = Carbon::today();
            $end = Carbon::today()->endOfDay();
            $filter = 'today';
        }

        // --- 1. DATA UNTUK STAT CARDS ---
        // $totalCustomers = User::customer()->whereBetween('created_at', [$start, $end])->count(); // usually not filtered, but we will filter it
        $totalCustomers = User::customer()->whereBetween('created_at', [$start, $end])->count();
        $todaysRevenue = ServiceHistory::whereBetween('service_date', [$start, $end])->sum('total_price');
        $pendingBookings = Booking::whereBetween('booking_date', [$start, $end])->where('status', 'pending')->count();
        $lowStockItems = Sparepart::where('stock', '<', 5)->count(); // Inventory is real-time, not affected by date

        // --- 2. DATA UNTUK GRAFIK PENDAPATAN ---
        $revenueLabels = [];
        $revenueData = [];
        $days = $start->diffInDays($end);
        
        if ($days == 0) {
            $revenueLabels[] = $start->format('D, j M');
            $revenueData[] = $todaysRevenue;
        } elseif ($days <= 31) {
            for ($i = 0; $i <= $days; $i++) {
                $date = $start->copy()->addDays($i);
                $revenueLabels[] = $date->format('D, j M');
                $revenueData[] = ServiceHistory::whereDate('service_date', $date)->sum('total_price');
            }
        } else {
            $months = $start->diffInMonths($end);
            for ($i = 0; $i <= $months; $i++) {
                $date = $start->copy()->addMonths($i);
                $revenueLabels[] = $date->format('M Y');
                $revenueData[] = ServiceHistory::whereMonth('service_date', $date->month)
                                               ->whereYear('service_date', $date->year)
                                               ->sum('total_price');
            }
        }

        // --- 3. DATA UNTUK GRAFIK JASA TERLARIS ---
        $topServices = ServiceDetail::where('itemable_type', Service::class)
            ->join('services', 'service_details.itemable_id', '=', 'services.id')
            ->join('service_histories', 'service_details.service_history_id', '=', 'service_histories.id')
            ->whereBetween('service_histories.service_date', [$start, $end])
            ->select('services.name', DB::raw('COUNT(service_details.id) as count'))
            ->groupBy('services.name')
            ->orderBy('count', 'DESC')
            ->take(5)
            ->get();

        $topServiceLabels = $topServices->pluck('name');
        $topServiceData = $topServices->pluck('count');

        // --- 4. DATA UNTUK RIWAYAT TRANSAKSI TERBARU ---
        $recentTransactions = ServiceHistory::with(['customer', 'vehicle'])
            ->whereBetween('service_date', [$start, $end])
            ->latest() // Ini = orderBy('created_at', 'DESC')
            ->take(5)  // Ambil 5 transaksi terbaru
            ->get();

        // --- 5. KIRIM SEMUA DATA KE VIEW ---
        return view('home', compact(
            'totalCustomers',
            'todaysRevenue',
            'pendingBookings',
            'lowStockItems',
            'revenueLabels',
            'revenueData',
            'topServiceLabels',
            'topServiceData',
            'recentTransactions',
        ));
    }
}
