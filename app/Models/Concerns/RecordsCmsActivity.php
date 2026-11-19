<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Trait reutilizable de auditoría administrativa para modelos Eloquent del CMS.
 *
 * Registra únicamente atributos modificados (`logOnlyDirty`), descarta guardados vacíos
 * y excluye columnas pesadas (cuerpos HTML extensos o árboles JSON del maquetador) y secretos
 * para mantener la tabla `activity_log` ligera y libre de datos sensibles.
 */
trait RecordsCmsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $logName = method_exists($this, 'activityLogChannel')
            ? $this->activityLogChannel()
            : 'cms';

        $entityLabel = method_exists($this, 'activityEntityLabel')
            ? $this->activityEntityLabel()
            : class_basename($this);

        return LogOptions::defaults()
            ->useLogName($logName)
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept([
                'password',
                'remember_token',
                'google_maps_api_key',
                'layout_json',
                'item_layout_json',
                'body',
                'fields_schema',
                'extra_fields_schema',
                'extra_fields_data',
                'scripts_before',
                'scripts_on_submit',
                'scripts_after',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($entityLabel): string {
                $verbo = match ($eventName) {
                    'created'  => 'Creó',
                    'updated'  => 'Actualizó',
                    'deleted'  => 'Eliminó',
                    'restored' => 'Restauró',
                    default    => ucfirst($eventName),
                };

                $titulo = $this->title
                    ?? $this->name
                    ?? $this->page_title
                    ?? $this->site_name
                    ?? $this->email
                    ?? ('#' . $this->getKey());

                return "{$verbo} {$entityLabel} «{$titulo}»";
            });
    }
}
