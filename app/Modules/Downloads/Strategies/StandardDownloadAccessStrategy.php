<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Strategies;

use App\Models\User;
use App\Modules\Downloads\Contracts\DownloadAccessDecision;
use App\Modules\Downloads\Contracts\DownloadAccessStrategyInterface;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use Illuminate\Http\Request;

/**
 * Estrategia estándar de acceso a descargas.
 *
 * - Respeta la visibilidad global del módulo ('admin_only' vs 'public').
 * - Permite a los administradores probar cualquier descarga (incluidos borradores o ítems de pago).
 * - Autoriza descargas gratuitas ('free'), exige sesión en ('registered') y deja preparado
 *   el bloqueo comercial ('paid') para cuando se conecte el módulo de pedidos/licencias.
 */
class StandardDownloadAccessStrategy implements DownloadAccessStrategyInterface
{
    public function evaluate(DownloadItem $item, ?User $user, Request $request): DownloadAccessDecision
    {
        $adminRole = (string) config('downloads.admin_role', 'admin');
        $isAdmin = $user !== null && method_exists($user, 'hasRole') && $user->hasRole($adminRole);

        if (!$item->hasFile()) {
            return DownloadAccessDecision::deny(
                'missing_file',
                'Este recurso aún no tiene un paquete de descarga disponible.',
                404
            );
        }

        // Los administradores siempre pueden probar la descarga desde el backend
        if ($isAdmin) {
            return DownloadAccessDecision::allow('admin_bypass');
        }

        // Si el catálogo está en modo solo backend ('admin_only'), ocultar completamente (404)
        $settings = DownloadSetting::cached();
        if (!$settings->isStrictlyPublic()) {
            return DownloadAccessDecision::deny(
                'catalog_private',
                'Recurso no encontrado.',
                404
            );
        }

        if (!$item->isPublished()) {
            return DownloadAccessDecision::deny(
                'unpublished',
                'Recurso no disponible.',
                404
            );
        }

        return match ($item->access_type) {
            DownloadItem::ACCESS_FREE => DownloadAccessDecision::allow('free_access'),

            DownloadItem::ACCESS_REGISTERED => $user !== null
                ? DownloadAccessDecision::allow('registered_user')
                : DownloadAccessDecision::deny(
                    'auth_required',
                    'Debes iniciar sesión para descargar este recurso gratuito.',
                    401
                ),

            DownloadItem::ACCESS_PAID => DownloadAccessDecision::deny(
                'payment_required',
                'Este plugin requiere una licencia comercial activa para su descarga.',
                402
            ),

            default => DownloadAccessDecision::deny(
                'unknown_access_type',
                'No tienes permiso para acceder a esta descarga.',
                403
            ),
        };
    }
}
