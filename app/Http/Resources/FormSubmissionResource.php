<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para el modelo FormSubmission.
 *
 * Formatea los datos de un envío para la bandeja de administración,
 * incluyendo metadatos de seguridad y estado de lectura.
 */
class FormSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'form_id'          => $this->form_id,
            'data'             => $this->data,
            'ip_address'       => $this->ip_address,
            'user_agent'       => $this->user_agent,
            'referer_url'      => $this->referer_url,
            'is_read'          => $this->is_read,
            'is_spam'          => $this->is_spam,
            'email_sent_admin' => $this->email_sent_admin,
            'email_sent_user'  => $this->email_sent_user,
            'created_at'       => $this->created_at?->toISOString(),
            'form'             => new FormResource($this->whenLoaded('form')),
        ];
    }
}
