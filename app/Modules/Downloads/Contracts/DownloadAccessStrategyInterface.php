<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Contracts;

use App\Models\User;
use App\Modules\Downloads\Models\DownloadItem;
use Illuminate\Http\Request;

/**
 * Contrato de autorización para evaluar quién puede descargar un recurso.
 *
 * Permite reemplazar o decorar la política de acceso (ej. añadir pasarela de pago o verificación
 * de suscripción activa) sin modificar los controladores ni el servicio de entrega binaria.
 */
interface DownloadAccessStrategyInterface
{
    public function evaluate(DownloadItem $item, ?User $user, Request $request): DownloadAccessDecision;
}
