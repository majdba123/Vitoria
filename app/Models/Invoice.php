<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable accounting snapshot of an order (spec §19). Never updated after
 * creation — a correction is a new order, not an edited invoice.
 */
class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'order_id',
        'vendor_id',
        'user_id',
        'subtotal_amount',
        'discount_total',
        'shipping_total',
        'tax_total',
        'grand_total',
        'currency',
        'payment_method',
        'issued_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
