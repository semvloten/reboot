<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['type', 'brand', 'model', 'serial_number', 'condition', 'accessories', 'asking_price', 'photos'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['priority' => 'integer', 'assigned_to_user_id' => 'integer', 'photos' => 'array', 'asking_price' => 'decimal:2', 'inspection' => 'array', 'inspected_at' => 'datetime'];
    }

    public function assignedInspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
