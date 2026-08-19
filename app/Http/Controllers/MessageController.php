<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Liste des conversations (trajets actifs) de l'utilisateur.
     * Récupère les trajets où l'utilisateur est soit conducteur, soit passager confirmé.
     */
    public function conversations(Request $request)
    {
        $stagiaireId = $request->user()->stagiaire->id;

        // Requête optimisée : on récupère les trajets avec leur dernier message en une seule fois
        $trajets = Trajet::where('conducteur_id', $stagiaireId)
            ->orWhereHas('reservations', function ($query) use ($stagiaireId) {
                $query->where('passager_id', $stagiaireId)
                      ->where('statut', 'CONFIRMEE');
            })
            ->with(['conducteur:id,nom,prenom,photo_profil'])
            // On charge la relation messages triée par date_envoi ET son auteur
            ->with(['messages' => function($query) {
                $query->with('auteur:id,nom,prenom')->orderBy('date_envoi', 'desc');
            }])
            ->orderByDesc('date_depart')
            ->get();

        return response()->json($trajets->map(function ($trajet) use ($stagiaireId) {
            $dernierMessage = $trajet->messages->first();

            return [
                'trajet_id' => $trajet->id,
                'lieu_depart' => $trajet->lieu_depart,
                'lieu_arrivee' => $trajet->lieu_arrivee,
                'date_depart' => $trajet->date_depart?->toIso8601String(),
                'dernier_message' => $dernierMessage ? [
                    'contenu' => $dernierMessage->contenu,
                    'auteur' => $dernierMessage->auteur_id === $stagiaireId ? 'Vous' : ($dernierMessage->auteur->prenom ?? 'Utilisateur'),
                    'cree_a' => $dernierMessage->date_envoi?->toIso8601String(),
                ] : null,
                'chauffeur' => [
                    'id' => $trajet->conducteur->id ?? null,
                    'nom' => $trajet->conducteur ? trim($trajet->conducteur->prenom . ' ' . $trajet->conducteur->nom) : 'Inconnu',
                    'photo_profil' => $trajet->conducteur->photo_profil_url ?? null,
                ],
            ];
        }));
    }

    // Envoyer un message sur un trajet — ouvert dès qu'il est visible, pas besoin de réservation
    public function store(Request $request, string $trajetId)
    {
        $trajet = Trajet::findOrFail($trajetId);

        $data = $request->validate([
            'contenu' => 'required|string|min:1',
        ]);

        $stagiaireId = $request->user()->stagiaire->id;

        $message = Message::create([
            'trajet_id' => $trajet->id,
            'auteur_id' => $stagiaireId,
            'contenu' => $data['contenu'],
        ]);

        $message->load('auteur:id,nom,prenom');

        return response()->json($this->formatMessage($message, $stagiaireId), 201);
    }

    // Historique des messages d'un trajet
    public function index(Request $request, string $trajetId)
    {
        $messages = Message::where('trajet_id', $trajetId)
            ->with('auteur:id,nom,prenom')
            ->orderByDesc('date_envoi')
            ->get();

        $stagiaireId = $request->user()->stagiaire->id;

        return response()->json(
    $messages->map(fn (Message $m) => $this->formatMessage($m, $stagiaireId))
);
    }

    /**
     * Formate un message selon la structure attendue par MessagesScreen
     * (clés "message", "auteur" en chaîne, "cree_a").
     */
    private function formatMessage(Message $message, string $userId): array
    {
        $estMoi = $message->auteur_id === $userId;

        return [
            'id' => $message->id,
            'message' => $message->contenu,
            'auteur' => $estMoi
                ? 'Vous'
                : trim(($message->auteur->prenom ?? '').' '.($message->auteur->nom ?? '')),
            'cree_a' => $message->date_envoi?->toIso8601String(),
        ];
    }
}
