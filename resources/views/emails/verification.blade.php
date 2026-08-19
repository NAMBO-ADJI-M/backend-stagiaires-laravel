<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification - StageLink</title>
</head>
<body style="margin: 0; padding: 32px 16px; background-color: #f3f4f6; color: #1f2937; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" style="max-width: 520px; width: 100%; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); overflow: hidden;" cellspacing="0" cellpadding="0" border="0">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 32px 24px 32px; text-align: center; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
                            <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: -0.5px;">StageLink</h1>
                            <p style="margin: 6px 0 0 0; color: #dbeafe; font-size: 14px;">Plateforme de gestion de stages & entraide</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px; text-align: center;">
                            <h2 style="margin: 0 0 12px 0; color: #111827; font-size: 20px; font-weight: 600;">Votre code de sécurité</h2>
                            <p style="margin: 0 0 24px 0; color: #4b5563; font-size: 15px; line-height: 1.5;">
                                Utilisez le code à 6 chiffres ci-dessous pour confirmer votre adresse email et accéder à votre espace :
                            </p>

                            <!-- Code Box -->
                            <div style="margin: 28px 0; padding: 18px 24px; background-color: #eff6ff; border: 2px dashed #bfdbfe; border-radius: 12px; display: inline-block;">
                                <span style="font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1d4ed8; font-family: 'Courier New', Courier, monospace;">{{ $code }}</span>
                            </div>

                            <p style="margin: 20px 0 0 0; color: #6b7280; font-size: 13px;">
                                ⏱️ Ce code est confidentiel et expirera dans <strong>15 minutes</strong>.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 32px 28px 32px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
                            <p style="margin: 0 0 8px 0; color: #9ca3af; font-size: 12px;">
                                Si vous n'avez pas demandé ce code, ignorez cet e-mail en toute sécurité.
                            </p>
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                &copy; {{ date('Y') }} StageLink - Tous droits réservés.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
