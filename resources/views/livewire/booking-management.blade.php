<div>
    {{-- 1. Header Halaman --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <h3>Manajemen Booking Online</h3>
        </div>
    </div>

    {{-- Tampilkan Flash Message --}}
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- 2. Baris Filter --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label>Filter Status</label>
                    <select wire:model.live="filterStatus" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu Konfirmasi</option>
                        <option value="confirmed">Dikonfirmasi</option>
                        <option value="cancelled">Dibatalkan</option>
                        <option value="completed">Selesai</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Filter Tanggal Booking</label>
                    <input type="date" wire:model.live="filterDate" class="form-control">
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Tabel Data Booking --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tanggal Booking</th>
                            <th>Pelanggan</th>
                            <th>Kendaraan</th>
                            <th>Keterangan Servis</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr>
                                <td>#{{ $booking->id }}</td>
                                <td>{{ $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date)->format('d M Y H:i') : '-' }}
                                </td>
                                <td>{{ $booking->customer->name ?? 'N/A' }}</td>
                                <td>{{ $booking->vehicle->license_plate ?? 'N/A' }}</td>
                                <td>{{ $booking->service_description ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $this->statusBadgeClass($booking->status) }}">
                                        {{ $this->statusLabel($booking->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($booking->status == 'pending' || $booking->status == 'confirmed')
                                        <a href="{{ route('transaksi.create', ['booking_id' => $booking->id]) }}"
                                            class="btn btn-success btn-sm">
                                            <i class="fas fa-cash-register"></i> Proses
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Belum ada data booking.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links --}}
            <div class="mt-3">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</div>
