<?php

namespace App\Services;

use App\Mail\FormNotificationMail;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use App\Services\FormValidationRuleBuilder;
use App\Services\HtmlSanitizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de Procesamiento de Envíos de Formularios.
 *
 * Orquesta todo el flujo de un envío: validación dinámica, sanitización,
 * detección de spam (honeypot), persistencia y despacho de emails.
 * Separado del controlador para mantener la lógica de negocio testeable
 * e independiente de HTTP.
 */
class FormSubmissionService
{
    public function __construct(
        private readonly FormValidationRuleBuilder $ruleBuilder,
        private readonly HtmlSanitizerService $sanitizer,
    ) {}

    /**
     * Procesar un envío de formulario completo.
     *
     * @throws ValidationException Si la validación falla.
     * @throws \RuntimeException Si el formulario no está activo o se excede el límite de envíos.
     */
    public function processSubmission(Form $form, Request $request): FormSubmission
    {
        // 1. Verificar que el formulario está activo
        $this->assertFormIsActive($form);

        // 2. Verificar límite de envíos por IP
        $this->assertNotExceedingIpLimit($form, $request->ip());

        // 3. Detectar spam via honeypot
        $isSpam = $this->detectHoneypotSpam($form, $request);

        // 3b. Verificar Cloudflare Turnstile si el formulario lo requiere
        if ($form->requires_captcha) {
            $this->validateTurnstile($request);
        }

        // 4. Validar datos dinámicamente según el schema
        $validatedData = $this->validateSubmission($form, $request);

        // 5. Sanitizar todos los campos de texto contra XSS
        $sanitizedData = $this->sanitizeData($form, $validatedData);

        // 6. Persistir el envío
        $submission = $form->submissions()->create([
            'data'         => $sanitizedData,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'referer_url'  => $request->headers->get('referer'),
            'is_spam'      => $isSpam,
            'is_read'      => false,
        ]);

        // 7. Despachar emails (solo si no es spam)
        if (!$isSpam) {
            $this->dispatchEmails($form, $submission);
        }

        return $submission;
    }

    /**
     * Verificar que el formulario está activo y acepta envíos.
     */
    private function assertFormIsActive(Form $form): void
    {
        if (!$form->is_active) {
            throw new \RuntimeException('Este formulario no está aceptando envíos en este momento.');
        }
    }

    /**
     * Verificar que la IP no ha excedido el límite de envíos configurado.
     */
    private function assertNotExceedingIpLimit(Form $form, string $ip): void
    {
        if ($form->max_submissions_per_ip === null) {
            return;
        }

        $count = $form->submissions()
            ->where('ip_address', $ip)
            ->count();

        if ($count >= $form->max_submissions_per_ip) {
            throw new \RuntimeException('Has alcanzado el límite de envíos permitidos para este formulario.');
        }
    }

    /**
     * Detectar spam mediante campo honeypot.
     *
     * Si el campo oculto (que un humano no rellena) tiene contenido → es un bot.
     */
    private function detectHoneypotSpam(Form $form, Request $request): bool
    {
        if (empty($form->honeypot_field)) {
            return false;
        }

        $honeypotValue = $request->input($form->honeypot_field, '');
        return !empty($honeypotValue);
    }

    /**
     * Validar los datos del envío contra las reglas generadas dinámicamente.
     *
     * @return array Datos validados (solo campos definidos en el schema)
     * @throws ValidationException
     */
    private function validateSubmission(Form $form, Request $request): array
    {
        $rules = $this->ruleBuilder->buildRules($form);
        $messages = $this->ruleBuilder->buildMessages($form);

        $validator = Validator::make(
            $request->all(),
            $rules,
            $messages
        );

        $validated = $validator->validate();

        // Extraer solo los datos de 'fields.*' y aplanarlos
        return $validated['fields'] ?? [];
    }

    /**
     * Verificar token de Cloudflare Turnstile.
     *
     * @throws ValidationException Si la verificación falla.
     */
    private function validateTurnstile(Request $request): void
    {
        $token = $request->input('turnstile_token') ?? $request->input('cf-turnstile-response');

        if (empty($token)) {
            // Fail-safe: si el widget no pudo renderizarse en el cliente
            // (red, extensiones, Cloudflare caído), permitir el envío.
            // Las demás capas (honeypot, CSRF, rate limiting) siguen activas.
            Log::info('Turnstile: envío sin token recibido (widget posiblemente no cargó)', [
                'ip' => $request->ip(),
            ]);
            return;
        }

        $secretKey = config('services.turnstile.secret_key');

        if (empty($secretKey)) {
            Log::warning('Turnstile secret key no configurada en services.turnstile.secret_key.');
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            if (!$response->successful() || !$response->json('success')) {
                Log::warning('Fallo en verificación de Cloudflare Turnstile', [
                    'ip'          => $request->ip(),
                    'error_codes' => $response->json('error-codes', []),
                ]);

                throw ValidationException::withMessages([
                    'turnstile' => 'La verificación de seguridad ha fallado. Por favor, inténtalo de nuevo.',
                ]);
            }

            Log::info('Turnstile: token validado con éxito por Cloudflare', [
                'ip'       => $request->ip(),
                'hostname' => $response->json('hostname'),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error comunicando con Cloudflare Turnstile: ' . $e->getMessage());
        }
    }

    /**
     * Sanitizar todos los campos de texto para prevenir XSS.
     *
     * Solo se sanitizan campos de texto libre. Los campos numéricos,
     * booleanos, de selección, etc. se dejan tal cual.
     */
    private function sanitizeData(Form $form, array $data): array
    {
        $textTypes = ['text', 'textarea', 'email', 'url', 'password', 'hidden'];
        $fieldMap = collect($form->fields_schema ?? [])->keyBy('name');

        foreach ($data as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $fieldType = $fieldMap->get($key)['type'] ?? 'text';

            if (in_array($fieldType, $textTypes, true)) {
                $data[$key] = $this->sanitizer->sanitize($value) ?? '';
            }
        }

        return $data;
    }

    /**
     * Despachar emails de notificación al admin y autorespuesta al usuario.
     *
     * Se envuelven en un bloque try/catch para registrar posibles fallos en el log
     * sin interrumpir la persistencia del envío ni la respuesta exitosa al usuario.
     */
    private function dispatchEmails(Form $form, FormSubmission $submission): void
    {
        try {
            $fieldMap = collect($form->fields_schema ?? [])->keyBy('name');

            $updates = [];

            // 1. Notificación al Administrador
            if ($form->email_admin_enabled) {
                $destinatariosRaw = $form->email_admin_to;

                if (!empty($destinatariosRaw)) {
                    $destinatarios = array_filter(
                        array_map('trim', explode(',', $destinatariosRaw)),
                        fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
                    );
                } else {
                    $siteContact = SiteSetting::cached()->contact_email ?? null;
                    $fallback = config('mail.from.address');
                    $destinatarios = array_filter([$siteContact ?: $fallback]);
                }

                if (!empty($destinatarios)) {
                    $subject = $this->renderVariables(
                        $form->email_admin_subject ?: 'Nuevo envío en {form.name}',
                        $form,
                        $submission,
                        $fieldMap
                    );

                    $body = $this->renderVariables(
                        $form->email_admin_template ?: '<p>Has recibido un nuevo mensaje en <strong>{form.name}</strong>:</p>{all_fields}',
                        $form,
                        $submission,
                        $fieldMap
                    );

                    Mail::to($destinatarios)->send(
                        new FormNotificationMail($form, $submission, $subject, $body, false)
                    );

                    $updates['email_sent_admin'] = true;
                }
            }

            // 2. Autorespuesta al Usuario
            if ($form->email_user_enabled) {
                $campoEmail = $form->email_user_field;

                // Fallback inteligente: si no se configuró explícitamente, buscar el primer campo de tipo email
                if (empty($campoEmail)) {
                    foreach ($form->fields_schema ?? [] as $f) {
                        $fType = $f['type'] ?? '';
                        $fName = $f['name'] ?? '';
                        if ($fType === 'email' || str_contains($fName, 'email') || str_contains($fName, 'correo')) {
                            $campoEmail = $fName;
                            break;
                        }
                    }
                }

                if (!empty($campoEmail)) {
                    $userEmail = trim((string) ($submission->data[$campoEmail] ?? ''));

                    if (!empty($userEmail) && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                        $subject = $this->renderVariables(
                            $form->email_user_subject ?: 'Hemos recibido tu solicitud',
                            $form,
                            $submission,
                            $fieldMap
                        );

                        $body = $this->renderVariables(
                            $form->email_user_template ?: '<p>Hemos recibido tu consulta y nos pondremos en contacto contigo lo antes posible.</p>',
                            $form,
                            $submission,
                            $fieldMap
                        );

                        Mail::to($userEmail)->send(
                            new FormNotificationMail($form, $submission, $subject, $body, true)
                        );

                        $updates['email_sent_user'] = true;
                    }
                }
            }

            if (!empty($updates)) {
                $submission->updateQuietly($updates);
            }
        } catch (\Throwable $e) {
            Log::error('Error despachando emails de formulario: ' . $e->getMessage(), [
                'form_id'       => $form->id,
                'submission_id' => $submission->id,
                'exception'     => $e,
            ]);
        }
    }

    /**
     * Renderizar y sustituir todas las variables dinámicas en plantillas de texto y HTML.
     */
    private function renderVariables(string $template, Form $form, FormSubmission $submission, $fieldMap): string
    {
        $siteSettings = SiteSetting::cached();
        $siteName = $siteSettings->site_name ?? config('app.name', 'CMS');
        $siteUrl = config('app.url', url('/'));

        // Generar tabla HTML de todos los campos para {all_fields} (inline CSS para emails)
        $allFieldsHtml = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">';
        foreach ($submission->data as $campoKey => $valor) {
            $campoMeta = $fieldMap->get($campoKey);
            $etiqueta = !empty($campoMeta['label']) ? $campoMeta['label'] : ucfirst(str_replace('_', ' ', $campoKey));
            $tipo = $campoMeta['type'] ?? 'text';

            if (is_array($valor)) {
                $valorTexto = htmlspecialchars(implode(', ', $valor));
            } elseif (is_bool($valor)) {
                $valorTexto = $valor ? 'Sí' : 'No';
            } else {
                $valorStr = (string) $valor;
                // Detectar emails para renderizar como enlace clicable
                if ($tipo === 'email' || filter_var($valorStr, FILTER_VALIDATE_EMAIL)) {
                    $valorTexto = '<a href="mailto:' . htmlspecialchars($valorStr) . '" style="color: #2563eb; text-decoration: none;">' . htmlspecialchars($valorStr) . '</a>';
                // Detectar teléfonos para renderizar como enlace clicable
                } elseif ($tipo === 'tel' || preg_match('/^\+?[\d\s\-().]{7,20}$/', $valorStr)) {
                    $tel = preg_replace('/[^\d+]/', '', $valorStr);
                    $valorTexto = '<a href="tel:' . htmlspecialchars($tel) . '" style="color: #2563eb; text-decoration: none;">' . htmlspecialchars($valorStr) . '</a>';
                } else {
                    $valorTexto = nl2br(htmlspecialchars($valorStr));
                }
            }

            $allFieldsHtml .= sprintf(
                '<tr>'
                . '<td style="padding: 12px 16px; text-align: left; border-bottom: 1px solid #f4f4f5; background-color: #fafafa; color: #52525b; font-weight: 600; font-size: 13px; width: 35%%; vertical-align: top;">%s</td>'
                . '<td style="padding: 12px 16px; text-align: left; border-bottom: 1px solid #f4f4f5; color: #18181b; font-weight: 400; font-size: 14px; vertical-align: top;">%s</td>'
                . '</tr>',
                htmlspecialchars($etiqueta),
                $valorTexto
            );
        }
        $allFieldsHtml .= '</table>';

        // Reemplazos globales fijos
        $replacements = [
            '{form.name}'            => htmlspecialchars($form->name),
            '{form.slug}'            => htmlspecialchars($form->slug),
            '{form.submission_date}' => $submission->created_at->format('d/m/Y H:i'),
            '{submission.id}'        => (string) $submission->id,
            '{submission.ip}'        => htmlspecialchars($submission->ip_address ?? ''),
            '{submission.referer}'   => htmlspecialchars($submission->referer_url ?? 'Directo'),
            '{site.name}'            => htmlspecialchars($siteName),
            '{site.url}'             => htmlspecialchars($siteUrl),
            '{all_fields}'           => $allFieldsHtml,
        ];

        // Reemplazos de campos específicos: {field.nombre_campo}
        foreach ($submission->data as $campoKey => $valor) {
            $tag = '{field.' . $campoKey . '}';
            if (is_array($valor)) {
                $replacements[$tag] = htmlspecialchars(implode(', ', $valor));
            } elseif (is_bool($valor)) {
                $replacements[$tag] = $valor ? 'Sí' : 'No';
            } else {
                $replacements[$tag] = htmlspecialchars((string) $valor);
            }
        }

        return strtr($template, $replacements);
    }
}
