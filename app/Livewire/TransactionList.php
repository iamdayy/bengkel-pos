<?php

namespace App\Livewire;

use App\Models\ServiceDetail;
use App\Models\ServiceHistory;
use App\Services\WaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class TransactionList extends Component
{
    use WithPagination;
    use WithFileUploads;

    private const STATUS_LABELS = [
        'pending' => 'Antrean',
        'in_progress' => 'Pengerjaan',
        'done' => 'Pembayaran',
        'paid' => 'Selesai',
    ];

    private const STATUS_CLASSES = [
        'pending' => 'bg-warning text-dark',
        'in_progress' => 'bg-info text-white',
        'done' => 'bg-success text-white',
        'paid' => 'bg-primary text-white',
    ];

    // Properti untuk filter
    public $search = '';

    public $filterStatus = '';

    public $filterDate = '';

    // Properti untuk modal detail
    public $isModalOpen = false;

    public $selectedTransaction = null;

    public $details = [];
    
    // Properti untuk unggah bukti pembayaran
    public $paymentProof;

    #[Layout(
        'layouts.admin'
    )]
    public function render()
    {
        $query = ServiceHistory::query()
            // Eager Loading (PENTING untuk performa)
            ->with(['customer', 'vehicle', 'mechanic']);

        // Terapkan filter pencarian (Nama Pelanggan atau No. Polisi)
        if (! empty($this->search)) {
            $query->whereHas('customer', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%');
            })->orWhereHas('vehicle', function ($q) {
                $q->where('license_plate', 'like', '%'.$this->search.'%');
            });
        }

        // Terapkan filter status
        if (! empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        // Terapkan filter tanggal
        if (! empty($this->filterDate)) {
            $query->whereDate('service_date', $this->filterDate);
        }

        // Ambil data dengan urutan terbaru dan pagination
        $transactions = $query->latest('service_date')->paginate(10);

        return view('livewire.transaction-list', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Tampilkan modal detail transaksi
     */
    public function showDetails($id)
    {
        // Ambil data transaksi induk (termasuk relasi)
        $this->selectedTransaction = ServiceHistory::with(['customer', 'vehicle', 'mechanic'])->findOrFail($id);

        // Ambil data detail (polimorfik itemable)
        $this->details = ServiceDetail::with('itemable')
            ->where('service_history_id', $id)
            ->get();

        $this->isModalOpen = true;
    }

    /**
     * Mengubah status transaksi
     */
    public function updateTransactionStatus($id, $status)
    {
        // Validasi status
        $allowedStatus = ['pending', 'in_progress', 'done', 'paid'];
        if (! in_array($status, $allowedStatus)) {
            session()->flash('error', 'Status tidak valid.'); // Kirim pesan error

            return;
        }

        $transaction = ServiceHistory::find($id);
        if ($transaction) {
            $validTransitions = [
                'pending' => ['in_progress'],
                'in_progress' => ['done'],
                'done' => ['paid'],
                'paid' => [],
            ];

            if ($transaction->status === $status) {
                return;
            }

            if (! in_array($status, $validTransitions[$transaction->status] ?? [], true)) {
                session()->flash('error', 'Transisi status tidak valid. Ikuti urutan antrean > pengerjaan > pembayaran > selesai.');

                return;
            }

            $transaction->status = $status;
            $transaction->save();

            // Kirim notifikasi singkat setiap kali status berubah
            if ($transaction->customer && $transaction->customer->phone && in_array($status, ['in_progress', 'done', 'paid'])) {
                try {
                    $wa = new WaService;
                    $shortMessage = "Halo {$transaction->customer->name}, status transaksi untuk kendaraan {$transaction->vehicle->license_plate} berubah menjadi ".$this->statusLabel($status).'.';
                    $wa->sendMessage($transaction->customer->phone, $shortMessage);
                } catch (\Throwable $e) {
                    // Jangan blokir alur jika pengiriman notifikasi gagal
                }
            }

            if ($status === 'paid') {

                // Kirim notifikasi invocice lengkap saat transaksi selesai
                if ($transaction->customer && $transaction->customer->phone) {
                    try {
                        $wa = new WaService;
                        $details = ServiceDetail::with('itemable')
                            ->where('service_history_id', $tx->id)
                            ->get();

                        // 2. Generate PDF dari view blade yang kita buat
                        $pdf = Pdf::loadView('pdf.nota', [
                            'tx' => $tx,
                            'details' => $details,
                        ]);

                        // 3. Simpan PDF ke folder 'storage/app/public/notas'
                        // Pastikan Anda sudah menjalankan "php artisan storage:link"
                        $filename = 'notas/nota-'.$tx->id.'-'.time().'.pdf';
                        Storage::disk('public')->put($filename, $pdf->output());

                        // 4. Dapatkan URL publik ke file PDF tersebut
                        // Penting: APP_URL di .env harus benar!
                        $fileUrl = Storage::disk('public')->url($filename);

                        // 5. Siapkan pesan (caption) untuk WA
                        $message = 'Terima kasih, '.$tx->customer->name."!\n\n".
                            'Servis untuk kendaraan '.$tx->vehicle->license_plate." telah selesai. Berikut kami lampirkan nota digital Anda.\n\n".
                            'Total Tagihan: Rp '.number_format($tx->total_price)."\n\n".
                            'Terima kasih atas kepercayaan Anda.'."\n\n".
                            $fileUrl;
                        $wa->sendMessage($transaction->customer->phone, $message);
                    } catch (\Throwable $e) {
                        // Jangan blokir alur jika inisialisasi WA gagal
                        return;
                    }
                }
            }

            session()->flash('message', 'Status transaksi #'.$id.' berhasil diubah menjadi '.$this->statusLabel($status));
        }
    }

    public function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst($status);
    }

    public function statusBadgeClass(string $status): string
    {
        return self::STATUS_CLASSES[$status] ?? 'bg-secondary text-white';
    }

    public function timelineStages(string $status, bool $compact = false): array
    {
        $statuses = array_keys(self::STATUS_LABELS);
        $currentIndex = array_search($status, $statuses, true);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        $timeline = [];
        foreach ($statuses as $index => $code) {
            $isCurrent = $index === $currentIndex;
            $isDone = $index < $currentIndex;

            $timeline[] = [
                'label' => self::STATUS_LABELS[$code],
                'short_label' => (string) ($index + 1),
                'title' => ($index + 1).'. '.self::STATUS_LABELS[$code],
                'class' => $isCurrent
                    ? 'bg-primary text-white'
                    : ($isDone ? 'bg-success text-white' : 'bg-light text-dark border'),
                'compact' => $compact,
            ];
        }

        return $timeline;
    }

    public function isQueueDraft($transaction): bool
    {
        return in_array($transaction->status, ['pending', 'in_progress']) && (int) $transaction->total_price === 0;
    }

    public function startWork($id)
    {
        $this->updateTransactionStatus($id, 'in_progress');
    }

    public function markAsDone($id)
    {
        $this->updateTransactionStatus($id, 'paid');
    }

    public function uploadPaymentProof($id)
    {
        $this->validate([
            'paymentProof' => 'required|image|max:2048', // Maksimal 2MB
        ]);

        $transaction = ServiceHistory::find($id);
        if ($transaction) {
            $path = $this->paymentProof->store('payment_proofs', 'public');
            $transaction->payment_proof = $path;
            
            // Jika status masih belum paid, otomatis ubah ke paid
            if ($transaction->status !== 'paid') {
                $transaction->status = 'paid';
            }
            
            $transaction->save();
            
            $this->paymentProof = null; // Reset property
            
            // Perbarui data modal jika sedang terbuka
            if ($this->selectedTransaction && $this->selectedTransaction->id === $id) {
                $this->selectedTransaction = $transaction;
            }

            session()->flash('message', 'Bukti pembayaran berhasil diunggah.');
        }
    }

    /**
     * Tutup modal
     */
    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->selectedTransaction = null;
        $this->details = [];
    }
}
