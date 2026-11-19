<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro inmutable de evento de descarga para auditoría y analítica en el backend.
 * Incorpora purgado masivo automático (`MassPrunable`) para evitar crecimiento infinito.
 */
class DownloadLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'download_item_id',
        'user_id',
        'ip_address',
        'ip_hash',
        'user_agent',
        'referer_url',
        'version_downloaded',
        'access_type_snapshot',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'download_item_id' => 'integer',
            'user_id'          => 'integer',
            'created_at'       => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return (string) config('downloads.tables.logs', 'download_logs');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(DownloadItem::class, 'download_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Consulta de purgado masivo eficiente sin hidratar modelos Eloquent en memoria.
     */
    public function prunable(): Builder
    {
        $retentionDays = max(7, (int) config('downloads.delivery.log_retention_days', 180));

        return static::where('created_at', '<=', now()->subDays($retentionDays));
    }
}
