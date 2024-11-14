<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'user_id', 'facility_id',
        'booking_start',
        'booking_end',
        'purpose',
        'duration',
        'participants',
        'status',
        'updateNotificationSent',
        'pdfNotificationSent',
    ];

    protected $casts = [
        'booking_start' => 'datetime', // Updated to datetime for DateTimePicker
        'booking_end' => 'datetime', // Updated to datetime for DateTimePicker
        'participants' => 'integer',
        'updateNotificationSent' => 'boolean',
        'pdfNotificationSent' => 'boolean',
    ];

    public function markUpdateNotified(): void
    {
        $this->update(['updateNotificationSent' => true]);
    }

    public function resetUpdateNotification(): void
    {
        $this->update(['updateNotificationSent' => false]);
    }

    public function approvers()
    {
        return $this->hasMany(Approver::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'booking_equipment')
            ->withPivot('quantity');
    }

    public function getFormattedEquipmentListAttribute(): string
    {
        if (!$this->relationLoaded('equipment') || $this->equipment->isEmpty()) {
            return 'No equipment requested';
        }

        return $this->equipment
            ->groupBy('name')
            ->map(function ($items) {
                $firstItem = $items->first();
                $totalQuantity = $items->sum('pivot.quantity');
                $name = ucwords(str_replace('_', ' ', $firstItem->name));
                return "{$name} ({$totalQuantity})";
            })
            ->join(', ');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }

    // In App\Models\Booking.php

    public function getFormattedSignatoriesAttribute()
    {
        if (!$this->reservation || !$this->reservation->signatories) {
            return 'No approvals yet';
        }

        return $this->reservation->signatories->map(function ($signatory) {
            $userName = $signatory->user ? $signatory->user->name : 'Unknown User';
            $status = ucfirst($signatory->status);
            $approvalDate = $signatory->approval_date
            ? $signatory->approval_date->format('Y-m-d H:i')
            : 'Not approved yet';

            return "{$userName} ({$signatory->role}): {$status} on {$approvalDate}";
        })->join("\n");
    }

    public function deleteRelatedRecords()
    {
        $this->reservation()->delete();
        $this->approvers()->delete();
        $this->equipment()->detach();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeInReview(Builder $query): Builder
    {
        return $query->where('status', 'in_review');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeDenied(Builder $query): Builder
    {
        return $query->where('status', 'denied');
    }

    /**
     * Retrieves a query builder with all of the booking's relations eagerly loaded.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function withAllRelations(): Builder
    {
        return static::with([
            'user',
            'facility',
            'reservation.signatories',
            'equipment',
            'approvers',
        ]);
    }
}
