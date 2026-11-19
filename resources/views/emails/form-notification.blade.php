<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectText }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #18181b; -webkit-text-size-adjust: 100%;">
    <!-- Contenedor principal centrado -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f5;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e7; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <!-- Cabecera con color dinámico y logotipo -->
                    <tr>
                        <td style="background-color: #{{ $emailHeaderColor ?? '059669' }}; padding: 24px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                @if(!empty($emailLogoUrl))
                                <tr>
                                    <td style="padding-bottom: 12px;">
                                        <img
                                            src="{{ $emailLogoUrl }}"
                                            alt="{{ $siteName ?? 'Logo' }}"
                                            height="40"
                                            style="height: 40px; width: auto; max-width: 200px; display: block;"
                                        />
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; font-size: 18px; font-weight: 700; letter-spacing: -0.01em; color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            {{ $form->name }}
                                        </h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="margin: 4px 0 0; font-size: 12px; color: rgba(255, 255, 255, 0.9); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            Envío registrado el {{ $submission->created_at->format('d/m/Y \a \l\a\s H:i') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Cuerpo del email -->
                    <tr>
                        <td style="padding: 32px; font-size: 14px; line-height: 1.6; color: #27272a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                            {!! $htmlBody !!}
                        </td>
                    </tr>

                    <!-- Pie del email -->
                    <tr>
                        <td style="padding: 20px 32px; background-color: #fafafa; border-top: 1px solid #f4f4f5; font-size: 11px; color: #a1a1aa; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                            <p style="margin: 0;">
                                Este correo ha sido generado automáticamente por el sistema de formularios de <strong>{{ $siteName }}</strong>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
