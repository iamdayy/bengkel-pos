@extends('layouts.admin')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <!-- Form Filter -->
    <form method="GET" action="{{ route('home') }}" class="mb-4">
        <div class="row align-items-end">
            <div class="col-md-3 mb-2">
                <label>Filter Waktu</label>
                <select name="filter" class="form-control" onchange="this.form.submit()">
                    <option value="today" {{ request('filter') == 'today' || !request('filter') ? 'selected' : '' }}>Hari Ini</option>
                    <option value="week" {{ request('filter') == 'week' ? 'selected' : '' }}>Minggu Ini</option>
                    <option value="month" {{ request('filter') == 'month' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="custom" {{ request('filter') == 'custom' ? 'selected' : '' }}>Range Tanggal</option>
                </select>
            </div>
            @if(request('filter') == 'custom')
            <div class="col-md-3 mb-2">
                <label>Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" required>
            </div>
            <div class="col-md-3 mb-2">
                <label>Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" required>
            </div>
            <div class="col-md-2 mb-2">
                <button type="submit" class="btn btn-primary w-100">Terapkan</button>
            </div>
            @endif
        </div>
    </form>

    @php
        $filterText = 'Hari Ini';
        if(request('filter') == 'week') $filterText = 'Minggu Ini';
        if(request('filter') == 'month') $filterText = 'Bulan Ini';
        if(request('filter') == 'custom') $filterText = 'Range Tanggal';
    @endphp

    <div class="row">

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Pendapatan ({{ $filterText }})</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp
                                {{ number_format($todaysRevenue, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Pelanggan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalCustomers }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Booking Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingBookings }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Stok Menipis (&lt; 5)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $lowStockItems }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Pendapatan ({{ $filterText }})</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">5 Jasa Servis Terlaris ({{ $filterText }})</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="topServicesChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">5 Transaksi Servis Terbaru ({{ $filterText }})</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Pelanggan</th>
                                    <th>Kendaraan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTransactions as $tx)
                                    <tr>
                                        <td>#{{ $tx->id }}</td>
                                        <td>{{ $tx->created_at->format('d M Y, H:i') }}</td>
                                        <td>{{ $tx->customer->name ?? 'N/A' }}</td>
                                        <td>{{ $tx->vehicle->license_plate ?? 'N/A' }}</td>
                                        <td>Rp {{ number_format($tx->total_price) }}</td>
                                        <td>
                                            @php
                                                $statusClass = 'bg-secondary';
                                                if ($tx->status == 'pending') {
                                                    $statusClass = 'bg-warning text-dark';
                                                }
                                                if ($tx->status == 'in_progress') {
                                                    $statusClass = 'bg-info';
                                                }
                                                if ($tx->status == 'done') {
                                                    $statusClass = 'bg-success';
                                                }
                                                if ($tx->status == 'paid') {
                                                    $statusClass = 'bg-primary';
                                                }
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ $tx->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Belum ada transaksi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-right mt-2">
                        <a href="{{ route('transaksi.index') }}">Lihat Semua Transaksi &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script>

    <script>
        // Fungsi helper untuk format Rupiah (opsional tapi bagus)
        function number_format(number, decimals, dec_point, thousands_sep) {
            number = (number + '').replace(',', '').replace(' ', '');
            var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousands_sep === 'undefined') ? '.' : thousands_sep,
                dec = (typeof dec_point === 'undefined') ? ',' : dec_point,
                s = '',
                toFixedFix = function(n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }

        // ---------------------------------
        // GRAFIK 1: PENDAPATAN (LINE CHART)
        // ---------------------------------
        var ctxRevenue = document.getElementById("revenueChart");
        var myLineChart = new Chart(ctxRevenue, {
            type: 'line',
            data: {
                labels: @json($revenueLabels), // Data dari Controller
                datasets: [{
                    label: "Pendapatan",
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: @json($revenueData), // Data dari Controller
                }],
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        time: {
                            unit: 'date'
                        },
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            // Format Rupiah
                            callback: function(value, index, values) {
                                return 'Rp ' + number_format(value);
                            }
                        },
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }],
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, chart) {
                            var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                            return datasetLabel + ': Rp ' + number_format(tooltipItem.yLabel);
                        }
                    }
                }
            }
        });

        // ---------------------------------
        // GRAFIK 2: JASA TERLARIS (DOUGHNUT)
        // ---------------------------------
        var ctxTopServices = document.getElementById("topServicesChart");
        var myPieChart = new Chart(ctxTopServices, {
            type: 'doughnut',
            data: {
                labels: @json($topServiceLabels), // Data dari Controller
                datasets: [{
                    data: @json($topServiceData), // Data dari Controller
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#c73123'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                },
                legend: {
                    display: true,
                    position: 'bottom'
                },
                cutoutPercentage: 80,
            },
        });
    </script>
@endpush
