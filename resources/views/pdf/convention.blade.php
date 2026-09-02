<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Convention de Stage - StageLink</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; line-height: 1.5; color: #333; font-size: 11pt; padding: 20px; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #1E3A8A; padding-bottom: 10px; }
        h1 { color: #1E3A8A; margin: 0; text-transform: uppercase; font-size: 18pt; }
        .section { margin-bottom: 25px; }
        .section-title { font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #ccc; margin-bottom: 10px; color: #1E3A8A; padding-bottom: 3px; font-size: 12pt; }
        .row { margin-bottom: 8px; }
        .label { font-weight: bold; width: 200px; display: inline-block; color: #666; }
        .content { margin-left: 20px; }
        .footer { margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; font-size: 9pt; color: #777; text-align: center; }
        .signature-box { margin-top: 40px; width: 100%; border-collapse: collapse; }
        .signature-box td { width: 50%; border: 1px solid #eee; padding: 20px; vertical-align: top; }
        .signature-label { font-weight: bold; text-decoration: underline; margin-bottom: 10px; display: block; }
        .certificate-stamp { border: 2px solid #10B981; color: #10B981; padding: 5px 10px; display: inline-block; font-weight: bold; border-radius: 5px; margin-top: 10px; font-size: 8pt; }
    </style>
</head>
<body>

<div class="header">
    @if(isset($logo_url) && $logo_url)
        <img src="{{ $logo_url }}" alt="Logo Entreprise" style="max-height: 60px; margin-bottom: 10px;">
    @endif
    <h1>Convention de Stage Professionnel</h1>
    <p>StageLink - Plateforme de Suivi et de Certification</p>
</div>

<div class="section">
    <div class="section-title">1. Cadre Administratif et Légal</div>
    <div class="row"><span class="label">Entreprise d'accueil :</span> <strong>{{ $autorisation->raison_sociale_custom ?? $entreprise->raison_sociale }}</strong></div>
    <div class="row"><span class="label">Adresse du siège :</span> {{ $autorisation->adresse_custom ?? $entreprise->adresse_libelle }}</div>
    <div class="row"><span class="label">Situation géographique :</span> {{ $autorisation->situation_geographique ?? 'N/A' }}</div>
    <div class="row"><span class="label">Secteur d'activité :</span> {{ $autorisation->secteur_activite_custom ?? $entreprise->secteur }}</div>

    <div class="row" style="margin-top: 10px;"><span class="label">Représentant Légal :</span> {{ $autorisation->representant_legal_nom ?? 'Non renseigné' }}</div>
    <div class="row"><span class="label">Fonction Représentant :</span> {{ $autorisation->representant_legal_fonction ?? 'N/A' }}</div>
    <div class="row"><span class="label">Contact Représentant :</span> {{ $autorisation->representant_legal_contact ?? 'N/A' }}</div>

    <div class="row" style="margin-top: 10px;"><span class="label">Le Stagiaire :</span> <strong>{{ strtoupper($stagiaire->nom) }} {{ $stagiaire->prenom }}</strong></div>
    <div class="row"><span class="label">Téléphone Stagiaire :</span> {{ $autorisation->stagiaire_telephone ?? $stagiaire->telephone }}</div>
    <div class="row"><span class="label">Établissement d'étude :</span> {{ $autorisation->etablissement_nom ?? $stagiaire->ecole }}</div>
    <div class="row"><span class="label">Année académique :</span> {{ $autorisation->stagiaire_annee_academique ?? 'N/A' }}</div>
    <div class="row"><span class="label">Objet du stage :</span> {{ $autorisation->objet_stage }}</div>
    <div class="row"><span class="label">Cursus de rattachement :</span> {{ $autorisation->cursus_rattachement }}</div>
</div>

<div class="section">
    <div class="section-title">2. Durée et Lieu</div>
    <div class="row"><span class="label">Période :</span> Du {{ $autorisation->date_debut }} au {{ $autorisation->date_fin }}</div>
    <div class="row"><span class="label">Lieu d'exécution :</span> {{ $autorisation->lieu_execution }}</div>
</div>

<div class="section">
    <div class="section-title">3. Conditions Matérielles</div>
    <div class="row"><span class="label">Durée hebdomadaire :</span> {{ $autorisation->duree_hebdomadaire }}</div>
    <div class="row"><span class="label">Jours de présence :</span> {{ is_array($autorisation->jours_presence) ? implode(', ', $autorisation->jours_presence) : $autorisation->jours_presence }}</div>
    <div class="row"><span class="label">Modalités de télétravail :</span> {{ $autorisation->teletravail_modalites }}</div>

    <div class="row" style="margin-top: 10px;"><span class="label">Gratification :</span>
        @if($autorisation->gratification_prevue)
            {{ $autorisation->gratification_montant }} € (Périodicité : {{ $autorisation->gratification_periodicite }})
        @else
            Sans gratification
        @endif
    </div>

    @if($autorisation->conges_absences)
        <div class="row"><span class="label">Congés et absences :</span></div>
        <div class="content"><em>{{ $autorisation->conges_absences }}</em></div>
    @endif

    @if($autorisation->conditions_stage)
        <div class="row"><span class="label">Autres avantages :</span></div>
        <div class="content"><em>{{ $autorisation->conditions_stage }}</em></div>
    @endif
</div>

<div class="section">
    <div class="section-title">4. Encadrement et Suivi</div>
    <div class="row"><span class="label">Maître de stage (Tuteur) :</span> <strong>{{ $autorisation->tuteur_nom ?? $autorisation->tuteur_designe }} {{ $autorisation->tuteur_prenom }}</strong></div>
    <div class="row"><span class="label">Fonction Tuteur :</span> {{ $autorisation->tuteur_fonction }}</div>
    <div class="row"><span class="label">Email Tuteur :</span> {{ $autorisation->tuteur_email }}</div>
    <div class="row"><span class="label">Téléphone Tuteur :</span> {{ $autorisation->tuteur_telephone }}</div>

    @if($autorisation->modalites_suivi_detail)
        <div class="row"><span class="label">Détail du suivi :</span></div>
        <div class="content">{{ $autorisation->modalites_suivi_detail }}</div>
    @endif
</div>

<div class="section">
    <div class="section-title">5. Engagement Numérique et Pointage</div>
    <p>Les parties acceptent l'usage de l'application StageLink pour certifier la présence effective du stagiaire sur le lieu d'exécution via le système de pointage GPS automatique.</p>
</div>

<table class="signature-box">
    <tr>
        <td>
            <span class="signature-label">Signature Entreprise</span>
            <div class="certificate-stamp">CERTIFIÉ PAR STAGELINK<br>{{ $autorisation->created_at->format('d/m/Y H:i') }}</div>
        </td>
        <td>
            <span class="signature-label">Signature Stagiaire</span>
            <div class="certificate-stamp">SIGNATURE NUMÉRIQUE<br>{{ $autorisation->updated_at->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

<div class="footer">
    Convention générée automatiquement par StageLink. <br>
    Identifiant unique de liaison : {{ $autorisation->id }}
</div>

</body>
</html>
