<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 22px; margin-bottom: 4px; color: #2c3e50; }
        .header p { color: #7f8c8d; font-size: 12px; }
        .content { margin-bottom: 30px; }
        .recommendation { font-style: italic; background: #f9f9f9; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0; }
        .section-title { font-size: 14px; font-weight: bold; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 4px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px 0; vertical-align: top; }
        .label { color: #7f8c8d; width: 200px; font-weight: bold; }
        .footer { margin-top: 50px; font-size: 11px; color: #95a5a6; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        .signature { margin-top: 40px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Carte d'Appui Stage</h1>
        <p>Recommandation professionnelle</p>
    </div>

    <div class="content">
        <p>À l'attention de <strong>{{ $carte->entreprise_destinataire_nom }}</strong>,</p>

        <p>Nous avons le plaisir de vous recommander <strong>{{ $stagiaire->prenom }} {{ $stagiaire->nom }}</strong> pour son parcours professionnel.</p>

        <div class="section-title">Informations sur le stage effectué</div>
        <table>
            <tr><td class="label">Entreprise émettrice</td><td>{{ $entreprise->raison_sociale }}</td></tr>
            <tr><td class="label">Période de stage</td><td>Du {{ \Carbon\Carbon::parse($carnet->date_debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($carnet->date_fin)->format('d/m/Y') }}</td></tr>
            <tr><td class="label">Poste / Métier</td><td>{{ $carnet->poste ?? $carnet->metier->nom }}</td></tr>
        </table>

        @if($carte->recommandation)
            <div class="section-title">Recommandation du tuteur</div>
            <div class="recommendation">
                {{ $carte->recommandation }}
            </div>
        @endif
    </div>

    <div class="signature">
        <p>Fait à {{ $entreprise->adresse_libelle }}, le {{ now()->format('d/m/Y') }}</p>
        <p><strong>{{ $entreprise->raison_sociale }}</strong></p>
    </div>

    <div class="footer">
        Ce document a été généré via la Plateforme Carnet de Stage suite à une évaluation positive.
    </div>
</body>
</html>
