<?php

namespace App\Services;

use App\Models\SiteSetting;

/**
 * Servicio para gestionar la configuración global del sitio.
 */
class SiteSettingService
{
    /**
     * Obtener la configuración actual (desde caché).
     */
    public function obtener(): SiteSetting
    {
        return SiteSetting::cached();
    }

    /**
     * Actualizar la configuración global.
     *
     * La caché se invalida automáticamente vía el evento `saved` del modelo.
     */
    public function actualizar(array $datos): SiteSetting
    {
        $setting = SiteSetting::firstOrCreate(['id' => 1]);

        // Sincronizar color de botón primario con color primario de marca si no se pasa explícitamente o si coincide con el anterior
        if (isset($datos['primary_color']) && empty($datos['button_primary_bg'])) {
            $datos['button_primary_bg'] = $datos['primary_color'];
        }
        if (isset($datos['secondary_color']) && empty($datos['button_secondary_bg'])) {
            $datos['button_secondary_bg'] = $datos['secondary_color'];
        }

        $setting->update($datos);

        return $setting->fresh();
    }
}
