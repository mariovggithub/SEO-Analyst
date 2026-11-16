<?php

namespace App\Models;

use App\Enums\AuditCategory;
use App\Enums\AuditStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $url
 * @property string $domain
 * @property AuditStatus $status
 * @property int|null $overall_score
 * @property int $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['url', 'domain', 'status', 'overall_score', 'created_by'])]
class Audit extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AuditStatus::class,
            'overall_score' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<AuditResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(AuditResult::class);
    }

    /**
     * @return HasOne<AuditResult, $this>
     */
    public function performanceResult(): HasOne
    {
        return $this->hasOne(AuditResult::class)->where('category', AuditCategory::Performance);
    }

    /**
     * @return HasOne<AuditResult, $this>
     */
    public function techStackResult(): HasOne
    {
        return $this->hasOne(AuditResult::class)->where('category', AuditCategory::TechStack);
    }

    /**
     * @return HasOne<AuditResult, $this>
     */
    public function onPageResult(): HasOne
    {
        return $this->hasOne(AuditResult::class)->where('category', AuditCategory::OnPage);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => AuditStatus::Processing]);
    }

    public function markAsCompleted(int $score): void
    {
        $this->update([
            'status' => AuditStatus::Completed,
            'overall_score' => $score,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => AuditStatus::Failed]);
    }
}

