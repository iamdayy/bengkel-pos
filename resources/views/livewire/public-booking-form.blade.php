<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">Formulir Booking Online</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center">Isi data di bawah ini untuk membuat janji servis.</p>

                    @if (session()->has('message'))
                        <div class="alert alert-success">
                            {{ session('message') }}
                            @if (session()->has('queue_number'))
                                <hr>
                                <h4 class="alert-heading text-center mb-0">Nomor Antrean Anda: <strong>{{ session('queue_number') }}</strong></h4>
                            @endif
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form wire:submit.prevent="storeBooking">

                        <h5 class="mt-4">1. Data Diri Anda</h5>
                        <hr>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" wire:model="name" class="form-control" id="name">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" wire:model="email" class="form-control" id="email">
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">No. WhatsApp</label>
                                <input type="text" wire:model="phone" class="form-control" id="phone"
                                    placeholder="cth: 0812345...">
                                @error('phone')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <h5 class="mt-4">2. Data Kendaraan</h5>
                        <hr>
                        <div class="mb-3">
                            <label for="license_plate" class="form-label">Nomor Polisi</label>
                            <input type="text" wire:model.live.debounce.500ms="license_plate" class="form-control" id="license_plate"
                                placeholder="cth: G 1234 AB">
                            @error('license_plate')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            
                            {{-- Boks Informasi Riwayat Servis Terakhir --}}
                            @if ($lastService)
                                <div class="alert alert-info mt-3 mb-0">
                                    <strong><i class="fas fa-info-circle"></i> Info Servis Terakhir:</strong><br>
                                    Tanggal: {{ \Carbon\Carbon::parse($lastService->service_date)->format('d M Y') }}<br>
                                    Catatan: {{ $lastService->notes ?? 'Tidak ada catatan' }}<br>
                                    Status: {{ ucfirst($lastService->status) }}
                                </div>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Merk Kendaraan</label>
                                <input type="text" wire:model="brand" class="form-control" id="brand"
                                    placeholder="cth: Honda">
                                @error('brand')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="model" class="form-label">Model Kendaraan</label>
                                <input type="text" wire:model="model" class="form-control" id="model"
                                    placeholder="cth: Vario 150">
                                @error('model')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <h5 class="mt-4">3. Rincian Servis</h5>
                        <hr>
                        <div class="mb-3">
                            <label for="booking_date" class="form-label">Pilih Tanggal & Jam Booking</label>
                            <input type="datetime-local" wire:model="booking_date" class="form-control"
                                id="booking_date" min="{{ now()->format('Y-m-d\TH:i') }}">
                            @error('booking_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Kirim Permintaan Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
