<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'payment_method',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'notes',
        'snapshot',
        'cancelled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public static function allStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = [
            self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED => [self::STATUS_DELIVERED],
            self::STATUS_DELIVERED => [],
            self::STATUS_CANCELLED => [],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? [], true);
    }

    public function transitionTo(string $newStatus, ?string $reason = null): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        if ($newStatus === self::STATUS_CANCELLED) {
            $this->cancelled_at = now();
            $this->restoreStock();
        }

        $this->save();

        Log::info("Order {$this->id} transitioned from {$oldStatus} to {$newStatus}", [
            'reason' => $reason,
        ]);

        return true;
    }

    protected function restoreStock(): void
    {
        foreach ($this->items as $item) {
            $product = $item->product;
            if ($product) {
                $product->stock_quantity += $item->quantity;
                $product->save();
            }
        }
    }
}
