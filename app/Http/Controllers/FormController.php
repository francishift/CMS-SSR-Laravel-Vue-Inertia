<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormRequest;
use App\Http\Resources\FormResource;
use App\Http\Resources\FormSubmissionResource;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador CRUD para formularios dinámicos (Panel Admin).
 *
 * Gestiona la creación, edición, duplicación y eliminación de
 * formularios, así como la bandeja de envíos recibidos.
 */
class FormController extends Controller
{
    /**
     * Listado paginado de formularios.
     */
    public function index(Request $request): Response|JsonResponse
    {
        // Endpoint ligero para el Page Builder: devuelve JSON sin Inertia
        if ($request->has('_builder_list') && ($request->wantsJson() || $request->ajax())) {
            $formularios = Form::active()
                ->select('id', 'name', 'slug')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json($formularios);
        }

        $formularios = Form::query()
            ->withCount('submissions')
            ->when($request->input('buscar'), function ($query, $buscar) {
                $query->where('name', 'like', "%{$buscar}%")
                      ->orWhere('slug', 'like', "%{$buscar}%");
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Formularios/Index', [
            'formularios' => FormResource::collection($formularios),
            'filtros'     => $request->only('buscar'),
        ]);
    }

    /**
     * Formulario de creación.
     */
    public function create(): Response
    {
        return Inertia::render('Formularios/Edit', [
            'formulario'          => null,
            'modo'                => 'crear',
            'turnstileConfigured' => $this->isTurnstileConfigured(),
        ]);
    }

    /**
     * Guardar nuevo formulario.
     */
    public function store(StoreFormRequest $request): RedirectResponse
    {
        $form = Form::create($request->validated());

        return redirect()
            ->route('panel.formularios.edit', $form)
            ->with('success', "Formulario «{$form->name}» creado correctamente.");
    }

    /**
     * Editor de formulario existente.
     */
    public function edit(Form $form): Response
    {
        return Inertia::render('Formularios/Edit', [
            'formulario'          => (new FormResource($form))->resolve(),
            'modo'                => 'editar',
            'turnstileConfigured' => $this->isTurnstileConfigured(),
        ]);
    }

    /**
     * Actualizar formulario existente.
     */
    public function update(StoreFormRequest $request, Form $form): RedirectResponse
    {
        $form->update($request->validated());

        return redirect()
            ->route('panel.formularios.edit', $form)
            ->with('success', "Formulario «{$form->name}» actualizado correctamente.");
    }

    /**
     * Duplicar un formulario existente.
     */
    public function duplicate(Form $form): RedirectResponse
    {
        $data = $form->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);
        $data['name'] = $form->name . ' (Copia)';
        $data['slug'] = $form->slug . '-copia-' . time();

        $newForm = Form::create($data);

        return redirect()
            ->route('panel.formularios.edit', $newForm)
            ->with('success', "Formulario duplicado correctamente.");
    }

    /**
     * Activar/desactivar formulario.
     */
    public function toggleActive(Form $form): RedirectResponse
    {
        $form->update(['is_active' => !$form->is_active]);
        $estado = $form->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Formulario «{$form->name}» {$estado}.");
    }

    /**
     * Eliminar formulario (y todos sus envíos en cascada).
     */
    public function destroy(Form $form): RedirectResponse
    {
        $nombre = $form->name;
        $form->delete();

        return redirect()
            ->route('panel.formularios.index')
            ->with('success', "Formulario «{$nombre}» eliminado permanentemente.");
    }

    // ──────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────

    /**
     * Comprobar si las claves de Turnstile están configuradas en .env.
     */
    private function isTurnstileConfigured(): bool
    {
        $siteKey   = config('services.turnstile.site_key');
        $secretKey = config('services.turnstile.secret_key');

        return !empty($siteKey) && !empty($secretKey);
    }

    // ──────────────────────────────────────────────
    // Bandeja de Envíos
    // ──────────────────────────────────────────────

    /**
     * Bandeja global paginada con todos los envíos de todos los formularios.
     *
     * Permite filtrar por formulario específico, estado (no leídos, leídos, spam)
     * y búsqueda por remitente/datos. Evita N+1 precargando la relación form.
     */
    public function allSubmissions(Request $request): Response
    {
        $formId = $request->input('form_id');
        $estado = $request->input('estado');
        $buscar = trim((string) $request->input('buscar'));

        $query = FormSubmission::with(['form:id,name,slug'])
            ->when(!empty($formId) && $formId !== 'todos', fn ($q) => $q->where('form_id', (int) $formId))
            ->when($estado === 'no_leidos', fn ($q) => $q->unread())
            ->when($estado === 'spam', fn ($q) => $q->where('is_spam', true))
            ->when($estado === 'leidos', fn ($q) => $q->where('is_read', true))
            ->when(!empty($buscar), function ($q) use ($buscar) {
                $q->where(function ($subQ) use ($buscar) {
                    $subQ->where('data', 'like', "%{$buscar}%")
                         ->orWhere('ip_address', 'like', "%{$buscar}%");
                });
            })
            ->orderByDesc('created_at');

        $envios = $query->paginate(20)->withQueryString();

        $formulariosDisponibles = Form::select('id', 'name', 'slug')
            ->orderBy('name')
            ->get()
            ->map(fn ($f) => [
                'id'   => $f->id,
                'name' => $f->name,
                'slug' => $f->slug,
            ]);

        $statsQuery = FormSubmission::query()
            ->when(!empty($formId) && $formId !== 'todos', fn ($q) => $q->where('form_id', (int) $formId));

        return Inertia::render('Formularios/Submissions', [
            'formulario'             => null, // null indica vista global
            'formulariosDisponibles' => $formulariosDisponibles,
            'envios'                 => FormSubmissionResource::collection($envios),
            'filtros'                => [
                'estado'  => $estado ?: 'todos',
                'form_id' => $formId ?: 'todos',
                'buscar'  => $buscar,
            ],
            'estadisticas'           => Inertia::lazy(fn () => [
                'total'     => (clone $statsQuery)->count(),
                'no_leidos' => (clone $statsQuery)->unread()->count(),
                'spam'      => (clone $statsQuery)->where('is_spam', true)->count(),
            ]),
        ]);
    }

    /**
     * Listado paginado de envíos de un formulario específico.
     */
    public function submissions(Request $request, Form $form): Response
    {
        $estado = $request->input('estado');
        $buscar = trim((string) $request->input('buscar'));

        $envios = $form->submissions()
            ->when($estado === 'no_leidos', fn ($q) => $q->unread())
            ->when($estado === 'spam', fn ($q) => $q->where('is_spam', true))
            ->when($estado === 'leidos', fn ($q) => $q->where('is_read', true))
            ->when(!empty($buscar), function ($q) use ($buscar) {
                $q->where(function ($subQ) use ($buscar) {
                    $subQ->where('data', 'like', "%{$buscar}%")
                         ->orWhere('ip_address', 'like', "%{$buscar}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $formulariosDisponibles = Form::select('id', 'name', 'slug')
            ->orderBy('name')
            ->get()
            ->map(fn ($f) => [
                'id'   => $f->id,
                'name' => $f->name,
                'slug' => $f->slug,
            ]);

        return Inertia::render('Formularios/Submissions', [
            'formulario'             => (new FormResource($form))->resolve(),
            'formulariosDisponibles' => $formulariosDisponibles,
            'envios'                 => FormSubmissionResource::collection($envios),
            'filtros'                => [
                'estado'  => $estado ?: 'todos',
                'form_id' => (string) $form->id,
                'buscar'  => $buscar,
            ],
            'estadisticas'           => Inertia::lazy(fn () => [
                'total'     => $form->submissions()->count(),
                'no_leidos' => $form->submissions()->unread()->count(),
                'spam'      => $form->submissions()->where('is_spam', true)->count(),
            ]),
        ]);
    }

    /**
     * Ver detalle de un envío.
     */
    public function showSubmission(Form $form, FormSubmission $submission): Response
    {
        // Marcar como leído al ver el detalle
        $submission->markAsRead();

        return Inertia::render('Formularios/SubmissionDetail', [
            'formulario' => (new FormResource($form))->resolve(),
            'envio'      => (new FormSubmissionResource($submission))->resolve(),
        ]);
    }

    /**
     * Marcar un envío como leído de forma explícita (soporta petición AJAX silenciosa).
     */
    public function markRead(Form $form, FormSubmission $submission): JsonResponse|RedirectResponse
    {
        return $this->markSubmissionRead($submission);
    }

    /**
     * Marcar un envío como leído directamente sin requerir instancia de Form en la ruta.
     */
    public function markSubmissionRead(FormSubmission $submission): JsonResponse|RedirectResponse
    {
        $submission->markAsRead();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Envío marcado como leído.');
    }

    /**
     * Marcar o desmarcar envío como spam (toggle).
     */
    public function markSpam(Form $form, FormSubmission $submission): RedirectResponse
    {
        return $this->markSubmissionSpam($submission);
    }

    /**
     * Alternar estado de spam de un envío directamente.
     */
    public function markSubmissionSpam(FormSubmission $submission): RedirectResponse
    {
        $nuevoEstado = !$submission->is_spam;
        $submission->update(['is_spam' => $nuevoEstado]);
        $mensaje = $nuevoEstado ? 'Envío marcado como spam.' : 'Envío restaurado como válido (desmarcado de spam).';

        return back()->with('success', $mensaje);
    }

    /**
     * Eliminar un envío.
     */
    public function destroySubmission(Form $form, FormSubmission $submission): RedirectResponse
    {
        return $this->destroySubmissionDirect($submission);
    }

    /**
     * Eliminar un envío directamente.
     */
    public function destroySubmissionDirect(FormSubmission $submission): RedirectResponse
    {
        $submission->delete();
        return back()->with('success', 'Envío eliminado.');
    }

    /**
     * Exportar envíos a CSV de un formulario específico.
     */
    public function exportCsv(Form $form)
    {
        $submissions = $form->submissions()
            ->notSpam()
            ->orderByDesc('created_at')
            ->get();

        if ($submissions->isEmpty()) {
            return back()->with('info', 'No hay envíos para exportar.');
        }

        // Obtener todas las claves de campos del schema
        $fieldNames = collect($form->getValidatableFields())->pluck('name')->all();
        $headers = array_merge(['ID', 'Fecha', 'IP'], $fieldNames);

        $callback = function () use ($submissions, $fieldNames, $headers) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 para que Excel lo interprete correctamente
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers, ';');

            foreach ($submissions as $submission) {
                $row = [
                    $submission->id,
                    $submission->created_at->format('d/m/Y H:i'),
                    $submission->ip_address,
                ];
                foreach ($fieldNames as $name) {
                    $value = $submission->data[$name] ?? '';
                    $row[] = is_array($value) ? implode(', ', $value) : $value;
                }
                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        $filename = "envios-{$form->slug}-" . now()->format('Y-m-d') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store',
        ]);
    }

    /**
     * Exportar envíos globales a CSV (respetando filtros de formulario o estado).
     */
    public function exportAllCsv(Request $request)
    {
        $formId = $request->input('form_id');
        $estado = $request->input('estado');

        $submissions = FormSubmission::with('form:id,name,slug')
            ->when(!empty($formId) && $formId !== 'todos', fn ($q) => $q->where('form_id', (int) $formId))
            ->when($estado === 'no_leidos', fn ($q) => $q->unread())
            ->when($estado === 'spam', fn ($q) => $q->where('is_spam', true))
            ->when($estado === 'leidos', fn ($q) => $q->where('is_read', true))
            ->when(empty($estado) || $estado === 'todos', fn ($q) => $q->notSpam())
            ->orderByDesc('created_at')
            ->get();

        if ($submissions->isEmpty()) {
            return back()->with('info', 'No hay envíos para exportar.');
        }

        // Obtener todos los campos dinámicos presentes en los envíos
        $fieldKeys = [];
        foreach ($submissions as $sub) {
            if (is_array($sub->data)) {
                foreach (array_keys($sub->data) as $key) {
                    if (!in_array($key, $fieldKeys, true)) {
                        $fieldKeys[] = $key;
                    }
                }
            }
        }

        $headers = array_merge(['ID', 'Formulario', 'Fecha', 'IP', 'Estado Lectura', 'Spam'], $fieldKeys);

        $callback = function () use ($submissions, $fieldKeys, $headers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel
            fputcsv($file, $headers, ';');

            foreach ($submissions as $submission) {
                $row = [
                    $submission->id,
                    $submission->form?->name ?? '—',
                    $submission->created_at?->format('d/m/Y H:i') ?? '',
                    $submission->ip_address,
                    $submission->is_read ? 'Leído' : 'No leído',
                    $submission->is_spam ? 'Spam' : 'Válido',
                ];
                foreach ($fieldKeys as $key) {
                    $value = $submission->data[$key] ?? '';
                    $row[] = is_array($value) ? implode(', ', $value) : $value;
                }
                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        $filename = 'envios-globales-' . now()->format('Y-m-d') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store',
        ]);
    }
}
