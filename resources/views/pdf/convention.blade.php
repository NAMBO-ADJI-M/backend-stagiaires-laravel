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
    <h1>CONVENTION DE STAGE PROFESSIONNEL</h1>
    <p style="font-weight: bold;">Réf : {{ $autorisation->id }}</p>
</div>

<div class="section">
    <div class="section-title">ENTRE LES SOUSSIGNÉS</div>
    <p><strong>1. L'ENTREPRISE D'ACCUEIL :</strong><br>
    {{ $autorisation->raison_sociale_custom ?? $entreprise->raison_sociale }}, située au {{ $autorisation->adresse_custom ?? $entreprise->adresse_libelle }}.<br>
    Représentée par {{ $autorisation->representant_legal_nom ?? 'son représentant légal' }} en qualité de {{ $autorisation->representant_legal_fonction ?? 'N/A' }}.</p>

    <p><strong>2. LE STAGIAIRE :</strong><br>
    M./Mme {{ strtoupper($stagiaire->nom) }} {{ $stagiaire->prenom }}, né(e) le {{ $stagiaire->date_naissance ? $stagiaire->date_naissance->format('d/m/Y') : '—' }}.<br>
    Demeurant au {{ $autorisation->stagiaire_adresse ?? $stagiaire->domicile_adresse }}.</p>

    <p><strong>3. L'ÉTABLISSEMENT D'ENSEIGNEMENT :</strong><br>
    {{ $autorisation->etablissement_nom ?? $stagiaire->ecole }}.<br>
    Cursus : {{ $autorisation->cursus_rattachement }}.</p>
</div>

<div class="section">
    <div class="section-title">ARTICLE 1 : OBJET DE LA CONVENTION</div>
    <p>La présente convention règle les rapports de l'entreprise d'accueil avec l'établissement d'enseignement et le stagiaire.<br>
    Le stage a pour objet : <strong>{{ $autorisation->objet_stage }}</strong>.<br>
    Les missions confiées sont : {{ $autorisation->poste }}.</p>
</div>

<div class="section">
    <div class="section-title">ARTICLE 2 : DURÉE ET VOLUME HORAIRE</div>
    <p>Le stage se déroulera du <strong>{{ $autorisation->date_debut->format('d/m/Y') }}</strong> au <strong>{{ $autorisation->date_fin->format('d/m/Y') }}</strong>.<br>
    La durée hebdomadaire est fixée à {{ $autorisation->duree_hebdomadaire }} heures.</p>
</div>

<div class="section">
    <div class="section-title">ARTICLE 3 : MODALITÉS D'EXÉCUTION</div>
    <p>Le lieu d'exécution du stage est : {{ $autorisation->lieu_execution }}.<br>
    Jours de présence : {{ is_array($autorisation->jours_presence) ? implode(', ', $autorisation->jours_presence) : $autorisation->jours_presence }}.<br>
    Modalités de télétravail : {{ $autorisation->teletravail_modalites ?? 'Non prévu' }}.</p>
</div>

<div class="section">
    <div class="section-title">ARTICLE 4 : GRATIFICATION ET AVANTAGES</div>
    <p>@if($autorisation->gratification_prevue)
        Le stagiaire percevra une gratification de <strong>{{ $autorisation->gratification_montant }} €</strong> payée selon une périodicité {{ $autorisation->gratification_periodicite }}.
    @else
        Le stage n'est pas assorti d'une gratification financière.
    @endif
    <br>Autres avantages : {{ $autorisation->conditions_stage ?? 'Néant' }}.</p>
</div>

<div class="section">
    <div class="section-title">ARTICLE 5 : ENCADREMENT ET SUIVI NUMÉRIQUE</div>
    <p>Le stagiaire est encadré par <strong>{{ $autorisation->tuteur_nom ?? $autorisation->tuteur_designe }} {{ $autorisation->tuteur_prenom }}</strong>.<br>
    Le suivi de l'assiduité est certifié par l'application <strong>StageLink</strong> via un système de pointage GPS automatique accepté par les parties.</p>
</div>

<div class="section">
    <div class="section-title">ARTICLE 6 : ASSURANCES ET RESPONSABILITÉ</div>
    <p>L'entreprise et le stagiaire déclarent être couverts par une assurance responsabilité civile pour toute la durée du stage.</p>
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
