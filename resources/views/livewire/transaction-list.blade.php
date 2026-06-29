<div>
    {{-- 1. Header Halaman --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <h3>Riwayat Transaksi Servis</h3>
        </div>
    </div>

    {{-- Tampilkan Flash Message (jika ada) --}}
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    {{-- 2. Baris Filter --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label>Cari (Pelanggan/No. Polisi)</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="Ketik pencarian...">
                </div>
                <div class="col-md-4">
                    <label>Filter Status</label>
                    <select wire:model.live="filterStatus" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="pending">Antrean</option>
                        <option value="in_progress">Pengerjaan</option>
                        <option value="done">Pembayaran</option>
                        <option value="paid">Selesai</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Filter Tanggal</label>
                    <input type="date" wire:model.live="filterDate" class="form-control">
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Tabel Data Transaksi --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Kendaraan</th>
                            <th>Mekanik</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $tx)
                            <tr>
                                <td>#{{ $tx->id }}</td>
                                <td>{{ $tx->service_date ? \Carbon\Carbon::parse($tx->service_date)->format('d M Y H:i') : '-' }}
                                </td>
                                <td>{{ $tx->customer->name ?? 'N/A' }}</td>
                                <td>{{ $tx->vehicle->license_plate ?? 'N/A' }}</td>
                                <td>{{ $tx->mechanic->name ?? 'N/A' }}</td>
                                <td>Rp {{ number_format($tx->total_price, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $this->statusBadgeClass($tx->status) }}">
                                        {{ $this->statusLabel($tx->status) }}
                                    </span>
                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                        @foreach ($this->timelineStages($tx->status, true) as $stage)
                                            <span class="badge {{ $stage['class'] }}"
                                                title="{{ $stage['title'] }}">{{ $stage['short_label'] }}</span>
                                        @endforeach
                                    </div>
                                    @if ($this->isQueueDraft($tx))
                                        <div class="mt-2">
                                            <span class="badge bg-light text-primary border border-primary"><i class="fas fa-clipboard-list"></i> Draft Antrean Kasir</span>
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    @if ($this->isQueueDraft($tx))
                                        <a href="{{ route('transaksi.create', ['queue_id' => $tx->id]) }}"
                                            class="btn btn-sm btn-outline-primary mb-1 w-100">
                                            Lanjutkan Antrean
                                        </a>

                                        @if ($tx->status == 'pending')
                                            <button type="button" class="btn btn-sm btn-info text-white mb-1 w-100"
                                                wire:click="startWork({{ $tx->id }})">
                                                Mulai Pengerjaan
                                            </button>
                                        @endif
                                    @endif

                                    @if ($tx->status == 'done')
                                        <div class="mt-2 p-2 border rounded bg-light">
                                            <small class="d-block mb-1 font-weight-bold text-muted">Upload Bukti Bayar</small>
                                            <input type="file" wire:model="paymentProof" class="form-control form-control-sm mb-1" id="proof-{{ $tx->id }}">
                                            @error('paymentProof') <span class="text-danger d-block small mb-1">{{ $message }}</span> @enderror
                                            <button type="button" class="btn btn-sm btn-success w-100"
                                                wire:click="uploadPaymentProof({{ $tx->id }})">
                                                Simpan & Selesaikan
                                            </button>
                                        </div>
                                    @endif

                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle {{ str_replace('bg-', 'btn-', $this->statusBadgeClass($tx->status)) }}" type="button"
                                            data-toggle="dropdown">
                                            Ubah Status
                                        </button>
                                        <div class="dropdown-menu">
                                            @if ($tx->status == 'pending')
                                                <a class="dropdown-item" href="#"
                                                    wire:click.prevent="updateTransactionStatus({{ $tx->id }}, 'in_progress')">Lanjut
                                                    ke Pengerjaan</a>
                                            @elseif ($tx->status == 'in_progress')
                                                <a class="dropdown-item" href="#"
                                                    wire:click.prevent="updateTransactionStatus({{ $tx->id }}, 'done')">Lanjut ke
                                                    Pembayaran</a>
                                            @elseif ($tx->status == 'done')
                                                <a class="dropdown-item" href="#"
                                                    wire:click.prevent="updateTransactionStatus({{ $tx->id }}, 'paid')">Lanjut ke
                                                    Selesai</a>
                                            @else
                                                <span class="dropdown-item text-muted">Sudah di tahap akhir</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Belum ada data transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links --}}
            <div class="mt-3">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>


    {{-- 4. Modal Detail Transaksi --}}
    @if ($isModalOpen && $selectedTransaction)
        <div class="modal fade show" tabindex="-1" style="display: block;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Transaksi #{{ $selectedTransaction->id }}</h5>
                        <button type="button" wire:click="closeModal()" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Info Kuitansi --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Pelanggan:</strong> {{ $selectedTransaction->customer->name ?? 'N/A' }}<br>
                                <strong>No. HP:</strong> {{ $selectedTransaction->customer->phone ?? 'N/A' }}
                            </div>
                            <div class="col-md-6 text-md-right">
                                <strong>Tanggal:</strong>
                                {{ \Carbon\Carbon::parse($selectedTransaction->service_date)->format('d M Y H:i') }}<br>
                                <strong>Status:</strong> <span
                                    class="badge {{ $this->statusBadgeClass($selectedTransaction->status) }}">
                                    {{ $this->statusLabel($selectedTransaction->status) }}
                                </span>
                                <div class="mt-2 d-flex flex-wrap gap-1 justify-content-md-end">
                                    @foreach ($this->timelineStages($selectedTransaction->status) as $stage)
                                        <span class="badge {{ $stage['class'] }}"
                                            title="{{ $stage['title'] }}">{{ $stage['label'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Kendaraan:</strong> {{ $selectedTransaction->vehicle->license_plate ?? 'N/A' }}
                                ({{ $selectedTransaction->vehicle->brand ?? '' }}
                                {{ $selectedTransaction->vehicle->model ?? '' }})
                            </div>
                            <div class="col-md-6 text-md-right">
                                <strong>Mekanik:</strong> {{ $selectedTransaction->mechanic->name ?? 'N/A' }}
                            </div>
                        </div>

                        {{-- Tabel Rincian Item --}}
                        <table class="table table-sm table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item</th>
                                    <th>Kategori</th>
                                    <th>Qty</th>
                                    <th>Harga Satuan</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details as $detail)
                                    <tr>
                                        <td>{{ $detail->itemable->name ?? 'Item Dihapus' }}</td>
                                        <td>{{ $detail->itemable_type == 'App\Models\Service' ? 'Jasa' : 'Sparepart' }}
                                        </td>
                                        <td>{{ $detail->quantity }}</td>
                                        <td>Rp {{ number_format($detail->price_at_transaction, 0, ',', '.') }}</td>
                                        <td>Rp
                                            {{ number_format($detail->price_at_transaction * $detail->quantity, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="4" class="text-right">GRAND TOTAL</td>
                                    <td>Rp {{ number_format($selectedTransaction->total_price, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>

                        {{-- Tampilkan Bukti Pembayaran jika ada --}}
                        @if($selectedTransaction->payment_proof)
                            <div class="mt-3 p-3 border rounded text-center bg-light">
                                <h6 class="font-weight-bold">Bukti Pembayaran</h6>
                                <a href="{{ Storage::url($selectedTransaction->payment_proof) }}" target="_blank">
                                    <img src="{{ Storage::url($selectedTransaction->payment_proof) }}" alt="Bukti Pembayaran" class="img-fluid rounded shadow-sm" style="max-height: 250px;">
                                </a>
                                <div class="mt-2">
                                    <small class="text-muted">Klik gambar untuk melihat ukuran penuh.</small>
                                </div>
                            </div>
                        @endif

                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="closeModal()" class="btn btn-secondary">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- Modal Backdrop --}}
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
