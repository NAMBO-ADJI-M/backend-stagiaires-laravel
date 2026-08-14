<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 20px; margin-bottom: 4px; }
        .header p { color: #666; font-size: 11px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 13px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 0; vertical-align: top; }
        .label { color: #666; width: 160px; }
        .niveau-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .niveau-table th, .niveau-table td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
        .niveau-table th { background: #f5f5f5; }
        .footer { margin-top: 40px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attestation de stage</h1>
        <p>Délivrée le {{ $attestation->date_generation?->format('d/m/Y') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Stagiaire</div>
        <table>
            <tr><td class="label">Nom et prénom</td><td>{{ $stagiaire->prenom }} {{ $stagiaire->nom }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Stage</div>
        <table>
            <tr><td class="label">Entreprise</td><td>{{ $entreprise->raison_sociale ?? $entreprise->nom }}</td></tr>
            <tr><td class="label">Période</td><td>{{ \Carbon\Carbon::parse($carnet->date_debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($carnet->date_fin)->format('d/m/Y') }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Compétences évaluées</div>
        @if($competences->isEmpty())
            <p>Aucune compétence évaluée pour ce stage.</p>
        @else
            <table class="niveau-table">
                <thead>
                    <tr><th>Compétence</th><th>Niveau atteint</th></tr>
                </thead>
                <tbody>
                    @foreach($competences as $c)
                        <tr>
                            <td>{{ $c['nom'] }}</td>
                            <td>{{ ucfirst(strtolower(str_replace('_', ' ', $c['niveau']))) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        Document généré automatiquement — Plateforme Carnet de Stage
    </div>
</body>
</html>