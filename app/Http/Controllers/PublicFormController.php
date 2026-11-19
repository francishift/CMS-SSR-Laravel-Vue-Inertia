<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\FormSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Controlador público para la recepción de envíos de formularios.
 *
 * Endpoint accesible sin autenticación, protegido por:
 * - CSRF (middleware web)
 * - Rate limiting (throttle en rutas)
 * - Honeypot + validación dinámica (FormSubmissionService)
 */
class PublicFormController extends Controller
{
    public function __construct(
        private readonly FormSubmissionService $submissionService,
    ) {}

    /**
     * Recibir y procesar un envío de formulario público.
     *
     * POST /formularios/{slug}/enviar
     */
    public function submit(Request $request, string $slug): JsonResponse
    {
        // Buscar formulario activo por slug (no por ID para anti-enumeración)
        $form = Form::active()->where('slug', $slug)->first();

        if (!$form) {
            return response()->json([
                'message' => 'Formulario no disponible.',
            ], 404);
        }

        try {
            $submission = $this->submissionService->processSubmission($form, $request);

            return response()->json([
                'success' => true,
                'message' => $form->success_message ?? 'Formulario enviado correctamente.',
                'redirect_url' => $form->success_redirect_url,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor, revisa los campos del formulario.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 429);
        }
    }

    /**
     * Obtener el esquema público de un formulario para renderizar en el frontend.
     *
     * GET /api/formularios/{slug}
     *
     * Solo expone los datos estrictamente necesarios para el renderizado.
     * Datos sensibles (templates de email, scripts, configuración de admin)
     * NUNCA se envían al cliente público.
     */
    public function schema(string $slug): JsonResponse
    {
        $form = Form::active()->where('slug', $slug)->first();

        if (!$form) {
            return response()->json(['message' => 'Formulario no disponible.'], 404);
        }

        return response()->json($form->toPublicSchema());
    }
}
