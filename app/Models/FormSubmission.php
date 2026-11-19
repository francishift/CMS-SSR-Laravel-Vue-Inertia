<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Envío de Formulario.
 *
 * Representa un envío individual recibido desde la web pública.
 * Almacena los datos enviados en JSON, metadatos de seguridad
 * y el estado de las notificaciones por email.
 *
 * @property int $id
 * @property int $form_id
 * @property array $data
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $referer_url
 * @property bool $is_read
 * @property bool $is_spam
 * @property bool $email_sent_admin
 * @property bool $email_sent_user
 */
class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'data',
        'ip_address',
        'user_agent',
        'referer_url',
        'is_read',
        'is_spam',
        'email_sent_admin',
        'email_sent_user',
    ];

    protected function casts(): array
    {
        return [
            'data'             => 'array',
            'is_read'          => 'boolean',
            'is_spam'          => 'boolean',
            'email_sent_admin' => 'boolean',
            'email_sent_user'  => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────────

    /**
     * Formulario al que pertenece este envío.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    /**
     * Solo envíos no leídos.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Solo envíos no marcados como spam.
     */
    public function scopeNotSpam($query)
    {
        return $query->where('is_spam', false);
    }

    /**
     * Filtrar por formulario específico.
     */
    public function scopeForForm($query, int $formId)
    {
        return $query->where('form_id', $formId);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Obtener el valor de un campo específico del envío.
     */
    public function getFieldValue(string $fieldName, mixed $default = null): mixed
    {
        return $this->data[$fieldName] ?? $default;
    }

    /**
     * Marcar como leído.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Marcar como spam.
     */
    public function markAsSpam(): bool
    {
        return $this->update(['is_spam' => true]);
    }
}
