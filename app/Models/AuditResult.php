<?php

namespace App\Models;

use App\Enums\AuditCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $audit_id
 * @property AuditCategory $category
 * @property array<string, mixed>|null $raw_data
 * @property array<string, mixed>|null $summary
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['audit_id', 'category', 'raw_data', 'summary'])]
class AuditResult extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AuditCategory::class,
            'raw_data' => 'array',
            'summary' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Audit, $this>
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}

