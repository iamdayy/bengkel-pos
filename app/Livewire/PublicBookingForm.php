<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PublicBookingForm extends Component
{
    // Properti untuk form
    public $name;

    public $email;

    public $phone;

    public $license_plate;

    public $brand;

    public $model;

    public $service_description;

    public $booking_date; // Akan jadi tipe datetime-local

    // Properti untuk menampilkan riwayat servis terakhir
    public $lastService = null;

    // Aturan validasi
    protected $rules = [
        'name' => 'required|string|min:3',
        'email' => 'nullable|email',
        'phone' => 'required|string',
        'license_plate' => 'required|string',
        'brand' => 'required|string', // Merk
        'model' => 'required|string', // Model
        'booking_date' => 'required|date|after_or_equal:today',
    ];

    #[Layout('layouts.public')]
    public function render()
    {
        return view('livewire.public-booking-form'); // 👈 Gunakan layout publik
    }

    /**
     * Hook ketika license_plate berubah (Livewire lifecycle)
     */
    public function updatedLicensePlate($value)
    {
        $this->lastService = null; // Reset saat berubah

        if (!empty($value)) {
            $vehicle = Vehicle::with(['latestService', 'owner'])
                ->where('license_plate', strtoupper($value))
                ->first();

            if ($vehicle) {
                // Auto-fill vehicle data
                $this->brand = $vehicle->brand;
                $this->model = $vehicle->model;
                
                // Auto-fill user data if owner exists
                if ($vehicle->owner) {
                    $this->name = $vehicle->owner->name;
                    $this->email = $vehicle->owner->email;
                    $this->phone = $vehicle->owner->phone;
                }

                if ($vehicle->latestService) {
                    $this->lastService = $vehicle->latestService;
                }
            }
        }
    }

    /**
     * Simpan data booking
     */
    public function storeBooking()
    {
        $this->validate();

        // Cek batasan antrean maksimal 10 per hari
        $bookingDate = \Carbon\Carbon::parse($this->booking_date)->toDateString();
        $dailyBookings = Booking::whereDate('booking_date', $bookingDate)
                                ->where('status', '!=', 'cancelled')
                                ->count();
                                
        if ($dailyBookings >= 10) {
            $this->addError('booking_date', 'Maaf, kuota booking pada tanggal ini sudah penuh (Maksimal 10 antrean). Silakan pilih tanggal lain.');
            return;
        }

        // 1. Cari atau Buat Pelanggan (User)
        $customer = null;

        if (!empty($this->email)) {
            $customer = User::where('email', $this->email)->first();
        }

        if (!$customer && !empty($this->phone)) {
            $customer = User::where('phone', $this->phone)->first();
        }

        if (!$customer) {
            $customer = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'role' => 'pelanggan',
                'password' => Hash::make(Str::random(10)),
            ]);
        }

        // 2. Cari atau Buat Kendaraan (Vehicle)
        // Kita gunakan no. polisi sebagai unik
        $vehicle = Vehicle::firstOrCreate(
            ['license_plate' => strtoupper($this->license_plate)],
            [
                'user_id' => $customer->id,
                'brand' => $this->brand,
                'model' => $this->model,
                'year' => date('Y'), // Asumsi tahun sekarang, bisa dibuat field
            ]
        );

        // Pastikan kendaraan ini milik customer yang login
        // (Jika kendaraan sudah ada tapi pemiliknya beda, ini harus ditangani)
        // Untuk skripsi ini, kita asumsikan no. polisi = 1 pemilik
        if ($vehicle->user_id != $customer->id) {
            session()->flash('error', 'Nomor polisi ini sudah terdaftar atas nama pelanggan lain.');

            return;
        }

        // 3. Buat Booking
        Booking::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'booking_date' => $this->booking_date,
            'status' => 'pending', // Status awal selalu pending
        ]);
        
        // Nomor antrean adalah total booking hari itu ditambah 1
        $queueNumber = $dailyBookings + 1;
        
        // 4. Kirim notifikasi WhatsApp
        try {
            $waService = new \App\Services\WaService();
            $message = "Halo *{$customer->name}*,\n\nTerima kasih telah melakukan booking servis di bengkel kami.\n\n*Detail Booking:*\n- Tgl: {$this->booking_date}\n- NOPOL: {$vehicle->license_plate}\n- Keluhan: {$this->service_description}\n\n*NOMOR ANTREAN ANDA: {$queueNumber}*\n\nMohon datang tepat waktu. Terima kasih!";
            $waService->sendMessage($customer->phone, $message);
        } catch (\Exception $e) {
            // Abaikan jika error WA agar tidak merusak flow booking
        }

        // 5. Kirim pesan sukses dan reset form
        session()->flash('message', 'Booking Anda telah diterima! Admin kami akan segera mengkonfirmasi jadwal Anda.');
        session()->flash('queue_number', $queueNumber);
        $this->reset();
    }
}
