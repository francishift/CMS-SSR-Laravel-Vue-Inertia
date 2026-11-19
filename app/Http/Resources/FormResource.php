<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para el modelo Form.
 *
 * Formatea y filtra los datos del formulario para el frontend,
 * evitando exponer campos sensibles como templates de email
 * o scripts a usuarios no autorizados.
 */
class FormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            'slug'                   => $this->slug,
            'description'            => $this->description,
            'fields_schema'          => $this->fields_schema,
            'settings'               => $this->settings,
            'email_admin_enabled'    => $this->email_admin_enabled,
            'email_admin_template'   => $this->email_admin_template,
            'email_admin_subject'    => $this->email_admin_subject,
            'email_admin_to'         => $this->email_admin_to,
            'email_user_enabled'     => $this->email_user_enabled,
            'email_user_template'    => $this->email_user_template,
            'email_user_subject'     => $this->email_user_subject,
            'email_user_field'       => $this->email_user_field,
            'scripts_before'         => $this->scripts_before,
            'scripts_on_submit'      => $this->scripts_on_submit,
            'scripts_after'          => $this->scripts_after,
            'success_message'        => $this->success_message,
            'success_redirect_url'   => $this->success_redirect_url,
            'max_submissions_per_ip' => $this->max_submissions_per_ip,
            'is_active'              => $this->is_active,
            'honeypot_field'         => $this->honeypot_field,
            'requires_captcha'       => $this->requires_captcha,
            'submissions_count'      => $this->whenCounted('submissions'),
            'unread_count'           => $this->when(
                $request->routeIs('panel.*'),
                fn () => $this->unread_count
            ),
            'created_at'             => $this->created_at?->toISOString(),
            'updated_at'             => $this->updated_at?->toISOString(),
        ];
    }
}
