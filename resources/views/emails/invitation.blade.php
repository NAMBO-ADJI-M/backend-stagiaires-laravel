<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation StageLink</title>
</head>
<body style="margin: 0; padding: 32px 16px; background-color: #f3f4f6; color: #1f2937; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" style="max-width: 520px; width: 100%; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); overflow: hidden;" cellspacing="0" cellpadding="0" border="0">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 32px 24px 32px; text-align: center; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                            <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: -0.5px;">StageLink</h1>
                            <p style="margin: 6px 0 0 0; color: #d1fae5; font-size: 14px;">Invitation de votre tuteur</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px; text-align: center;">
                            <h2 style="margin: 0 0 12px 0; color: #111827; font-size: 20px; font-weight: 600;">Bonjour {{ $prenom }},</h2>
                            <p style="margin: 0 0 24px 0; color: #4b5563; font-size: 15px; line-height: 1.5;">
                                L'entreprise <strong>{{ $entreprise }}</strong> vous invite à rejoindre son espace sur StageLink pour assurer le suivi de votre stage.
                            </p>

                            <p style="margin: 0 0 24px 0; color: #4b5563; font-size: 15px; line-height: 1.5;">
                                Voici votre <strong>code de rattachement</strong> à enregistrer dans l'application :
                            </p>

                            <!-- Code Box -->
                            <div style="margin: 20px 0; padding: 18px 24px; background-color: #ecfdf5; border: 2px dashed #6ee7b7; border-radius: 12px; display: inline-block;">
                                <span style="font-size: 32px; font-weight: 800; letter-spacing: 4px; color: #065f46; font-family: 'Courier New', Courier, monospace;">{{ $code }}</span>
                            </div>

                            <p style="margin: 24px 0 0 0; color: #4b5563; font-size: 15px; line-height: 1.5;">
                                Connectez-vous sur l'application StageLink, créez ou ouvrez votre carnet de stage et saisissez ce code pour valider votre rattachement.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 32px 28px 32px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
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
