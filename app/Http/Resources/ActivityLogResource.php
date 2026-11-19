<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * API Resource administrativo para registros de auditoría del CMS (`Spatie\Activitylog\Models\Activity`).
 *
 * Formatea cambios (`attribute_changes`), actor, módulo e IP sin exponer modelos Eloquent crudos.
 *
 * @mixin Activity
 */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $props = $this->properties ? $this->properties->toArray() : [];
        $changesRaw = $this->attribute_changes ? $this->attribute_changes->toArray() : [];

        $newAttrs = is_array($changesRaw['attributes'] ?? null) ? $changesRaw['attributes'] : [];
        $oldAttrs = is_array($changesRaw['old'] ?? null) ? $changesRaw['old'] : [];

        $cambios = [];
        foreach ($newAttrs as $campo => $valorNuevo) {
            $valorAnterior = $oldAttrs[$campo] ?? null;
            $cambios[] = [
                'campo'   => (string) $campo,
                'antes'   => self::formatearValorCambio($valorAnterior),
                'despues' => self::formatearValorCambio($valorNuevo),
            ];
        }

        return [
            'id'               => $this->id,
            'log_name'         => $this->log_name ?: 'cms',
            'event'            => $this->event ?: 'info',
            'description'      => $this->description,
            'subject_type'     => $this->subject_type ? class_basename($this->subject_type) : null,
            'subject_id'       => $this->subject_id,
            'causer_name'      => $this->causer?->name ?? ($props['email'] ?? 'Sistema / Invitado'),
            'causer_email'     => $this->causer?->email ?? ($props['email'] ?? null),
            'ip_address'       => $props['ip'] ?? null,
            'user_agent'       => $props['user_agent'] ?? null,
            'cambios'          => $cambios,
            'tiene_cambios'    => !empty($cambios),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'created_at_exact' => $this->created_at?->format('d/m/Y H:i:s'),
        ];
    }

    private static function formatearValorCambio(mixed $val): string
    {
        if ($val === null || $val === '') {
            return '—';
        }
        if (is_bool($val)) {
            return $val ? 'Sí' : 'No';
        }
        if (is_array($val)) {
            $json = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
            return mb_strlen($json) > 180 ? mb_substr($json, 0, 180) . '…' : $json;
        }
        $str = trim((string) $val);
        return mb_strlen($str) > 180 ? mb_substr($str, 0, 180) . '…' : $str;
    }
}
