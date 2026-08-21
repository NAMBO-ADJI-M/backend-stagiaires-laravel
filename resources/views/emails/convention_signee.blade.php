<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { text-align: center; border-bottom: 2px solid #1E3A8A; padding-bottom: 10px; }
        .content { padding: 20px 0; }
        .footer { text-align: center; font-size: 0.8em; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #1E3A8A; color: #fff; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="color: #1E3A8A;">StageLink</h2>
        </div>
        <div class="content">
            <p>Bonjour,</p>
            <p>Nous avons le plaisir de vous informer que la <strong>Convention de Stage</strong> a été signée numériquement par toutes les parties.</p>
            <p>Vous trouverez le document officiel en pièce jointe de cet e-mail.</p>

            <p><strong>Détails du stage :</strong><br>
            • Entreprise : {{ $entreprise->raison_sociale }}<br>
            • Stagiaire : {{ $stagiaire->prenom }} {{ $stagiaire->nom }}<br>
            • Période : Du {{ $autorisation->date_debut }} au {{ $autorisation->date_fin }}</p>

            <p>Le pointage GPS automatique est désormais actif pour la durée du stage.</p>

            <p>Bon stage à vous !</p>
        </div>
        <div class="footer">
            <p>Ceci est un message automatique, merci de ne pas y répondre.<br>
            &copy; {{ date('Y') }} StageLink - La certification de présence au service des stagiaires.</p>
        </div>
    </div>
</body>
</html>
