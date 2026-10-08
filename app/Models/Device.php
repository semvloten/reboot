<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Onderdeel: Apparaatgegevens, checklist en relaties met verkoper, keurmeester en reserverende klant.
 * Eisen: FE-02, FE-03, FE-07, FE-12, TE-02, TE-06.
 * Bouw: T-07 (aanmelding), T-13 (checklist), T-22 (reservering), T-31 (structuur).
 * Geplande controle: T-08, T-14, T-23, T-32.
 */
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
