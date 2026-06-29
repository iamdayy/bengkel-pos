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
        'email' => 'nullable|email|unique:users,email',
        'phone' => 'required|string',
        'license_plate' => 'required|string',
        'brand' => 'required|string', // Merk
        'model' => 'required|string', // Model
        'service_description' => 'required|string|min:10', // Keluhan
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
            $vehicle = Vehicle::with('latestService')
                ->where('license_plate', strtoupper($value))
                ->first();

            if ($vehicle && $vehicle->latestService) {
                $this->lastService = $vehicle->latestService;
            }
        }
    }

    /**
     * Simpan data booking
     */
    public function storeBooking()
    {
        $this->validate();

        // 1. Cari atau Buat Pelanggan (User)
        // Kita gunakan email sebagai unik, tapi bisa juga phone
        $customer = User::firstOrCreate(
            ['email' => $this->email, 'phone' => $this->phone],
            [
                'name' => $this->name,
                'phone' => $this->phone,
                'role' => 'pelanggan',
                'password' => Hash::make(Str::random(10)), // Buat password acak
            ]
        );

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
            'service_description' => $this->service_description,
            'status' => 'pending', // Status awal selalu pending
        ]);

        // 4. Kirim pesan sukses dan reset form
        session()->flash('message', 'Booking Anda telah diterima! Admin kami akan segera mengkonfirmasi jadwal Anda.');
        $this->reset();
    }
}
