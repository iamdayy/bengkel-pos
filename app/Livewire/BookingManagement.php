<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Booking; // 👈 Gunakan Model Booking
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class BookingManagement extends Component
{
    use WithPagination;

    // Properti untuk filter
    public $filterStatus = '';
    public $filterDate = '';

    #[Layout('layouts.admin')]
    public function render()
    {
        $query = Booking::query()
            // Eager Loading untuk performa
            ->with(['customer', 'vehicle']);

        // Terapkan filter status
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        // Terapkan filter tanggal
        if (!empty($this->filterDate)) {
            $query->whereDate('booking_date', $this->filterDate);
        }

        // Ambil data dengan urutan terbaru (booking terbaru di atas)
        $bookings = $query->latest('booking_date')->paginate(10);

        return view('livewire.booking-management', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Mengubah status booking
     */
    public function updateBookingStatus($id, $status)
    {
        // Validasi status
        if (!in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
            session()->flash('error', 'Status tidak valid.');
            return;
        }

        $booking = Booking::find($id);
        if ($booking) {
            $booking->status = $status;
            $booking->save();
            session()->flash('message', 'Status booking berhasil diubah.');
        }
    }

    public function statusLabel($status)
    {
        $labels = [
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'cancelled' => 'Dibatalkan',
            'completed' => 'Selesai'
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    public function statusBadgeClass($status)
    {
        $classes = [
            'pending' => 'bg-warning text-dark',
            'confirmed' => 'bg-info text-white',
            'cancelled' => 'bg-danger text-white',
            'completed' => 'bg-success text-white'
        ];
        return $classes[$status] ?? 'bg-secondary text-white';
    }
}
