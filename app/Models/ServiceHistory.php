<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'mechanic_id',
        'vehicle_id',
        'total_price',
        'status',
        'service_date',
        'notes',
        'payment_proof'
    ];

    protected $casts = [
        'service_date' => 'date',
    ];

    /**
     * Get the customer that owns the ServiceHistory.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the mechanic that assigned to the ServiceHistory.
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    /**
     * Get the vehicle for the ServiceHistory.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get all of the details for the ServiceHistory.
     */
    public function details(): HasMany
    {
        return $this->hasMany(ServiceDetail::class);
    }
}
