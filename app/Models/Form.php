<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Modelo de Formulario Dinámico.
 *
 * Representa la definición completa de un formulario: campos, emails,
 * scripts de eventos y políticas de seguridad. Los formularios se
 * incrustan en el Page Builder como elemento 'form' y se identifican
 * públicamente por su slug.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array $fields_schema
 * @property array|null $settings
 * @property bool $email_admin_enabled
 * @property string|null $email_admin_template
 * @property string|null $email_admin_subject
 * @property string|null $email_admin_to
 * @property bool $email_user_enabled
 * @property string|null $email_user_template
 * @property string|null $email_user_subject
 * @property string|null $email_user_field
 * @property string|null $scripts_before
 * @property string|null $scripts_on_submit
 * @property string|null $scripts_after
 * @property string $success_message
 * @property string|null $success_redirect_url
 * @property int|null $max_submissions_per_ip
 * @property bool $is_active
 * @property string|null $honeypot_field
 * @property bool $requires_captcha
 */
class Form extends Model
{
    use HasFactory, \App\Models\Concerns\RecordsCmsActivity;

    public function activityLogChannel(): string { return 'contenido'; }
    public function activityEntityLabel(): string { return 'formulario'; }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'fields_schema',
        'settings',
        'email_admin_enabled',
        'email_admin_template',
        'email_admin_subject',
        'email_admin_to',
        'email_user_enabled',
        'email_user_template',
        'email_user_subject',
        'email_user_field',
        'scripts_before',
        'scripts_on_submit',
        'scripts_after',
        'success_message',
        'success_redirect_url',
        'max_submissions_per_ip',
        'is_active',
        'honeypot_field',
        'requires_captcha',
    ];

    protected function casts(): array
    {
        return [
            'fields_schema'        => 'array',
            'settings'             => 'array',
            'email_admin_enabled'  => 'boolean',
            'email_user_enabled'   => 'boolean',
            'is_active'            => 'boolean',
            'requires_captcha'     => 'boolean',
            'max_submissions_per_ip' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────────

    /**
     * Envíos recibidos para este formulario.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    /**
     * Solo formularios activos (habilitados para el público).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ──────────────────────────────────────────────
    // Mutators
    // ──────────────────────────────────────────────

    /**
     * Generar slug automáticamente al crear si no se proporciona.
     */
    protected static function booted(): void
    {
        static::creating(function (Form $form) {
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->name);
            }

            // Asegurar unicidad del slug
            $baseSlug = $form->slug;
            $counter = 1;
            while (static::where('slug', $form->slug)->exists()) {
                $form->slug = $baseSlug . '-' . $counter++;
            }
        });

        static::saved(static function () {
            Cache::forget('cms_forms_has_active_captcha');
            Cache::forget('cms_csp_active_integrations');
        });

        static::deleted(static function () {
            Cache::forget('cms_forms_has_active_captcha');
            Cache::forget('cms_csp_active_integrations');
        });
    }

    /**
     * Indica desde caché si existe al menos un formulario público activo que utilice captcha Turnstile.
     */
    public static function hasAnyCaptchaEnabled(): bool
    {
        try {
            return (bool) Cache::remember(
                'cms_forms_has_active_captcha',
                86400,
                static fn () => static::query()
                    ->where('is_active', true)
                    ->where('requires_captcha', true)
                    ->exists()
            );
        } catch (\Throwable) {
            return false;
        }
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Obtener solo los campos que requieren validación (excluir decorativos).
     *
     * @return array
     */
    public function getValidatableFields(): array
    {
        $decorativeTypes = ['separator', 'heading', 'paragraph'];

        return collect($this->fields_schema ?? [])
            ->filter(fn (array $field) => !in_array($field['type'] ?? '', $decorativeTypes, true))
            ->values()
            ->all();
    }

    /**
     * Obtener el nombre del campo configurado como email del usuario.
     */
    public function getUserEmailFieldName(): ?string
    {
        return $this->email_user_field;
    }

    /**
     * Devuelve el esquema público seguro del formulario para el frontend.
     * NUNCA expone reglas internas de validación, destinatarios de email, ni configuraciones del servidor.
     */
    public function toPublicSchema(): array
    {
        $clavesPublicas = [
            'id', 'type', 'name', 'label', 'placeholder', 'required',
            'options', 'width', 'conditional', 'default_value',
            'min', 'max', 'min_length', 'max_length',
            'min_date', 'max_date', 'max_rating', 'content',
            'link_url', 'link_target',
        ];

        $fieldsSchemaFiltrado = collect($this->fields_schema ?? [])
            ->map(fn (array $campo) => collect($campo)->only($clavesPublicas)->all())
            ->values()
            ->all();

        return [
            'name'            => $this->name,
            'slug'            => $this->slug,
            'fields_schema'   => $fieldsSchemaFiltrado,
            'settings'        => [
                'submit_button_text'  => $this->settings['submit_button_text'] ?? 'Enviar',
                'submit_button_color' => $this->settings['submit_button_color'] ?? 'emerald',
                'submit_button_align' => $this->settings['submit_button_align'] ?? 'left',
                'success_feedback'    => $this->settings['success_feedback'] ?? 'both',
            ],
            'requires_captcha'   => (bool) $this->requires_captcha,
            'turnstile_site_key' => $this->requires_captcha ? config('services.turnstile.site_key') : null,
            'scripts'            => [
                'before'    => $this->scripts_before,
                'on_submit' => $this->scripts_on_submit,
                'after'     => $this->scripts_after,
            ],
            'success_message' => $this->success_message,
            'honeypot_field'  => $this->honeypot_field,
        ];
    }

    /**
     * Conteo de envíos no leídos (optimizado para listados).
     */
    public function getUnreadCountAttribute(): int
    {
        return $this->submissions()->where('is_read', false)->count();
    }
}
