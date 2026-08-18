<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class TestEmailController extends Controller
{
    public function sendTestEmail(Request $request)
    {
        try {
            $email = $request->query('email', 'adjimelinklaanambo@gmail.com');

            Mail::raw('Ceci est un test de connexion SMTP pour StageLink ! 🚀', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test SMTP - StageLink')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Email de test envoyé avec succès !',
                'to' => $email,
                'from' => config('mail.from.address'),
                'mailer' => config('mail.default')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'hint' => 'Vérifiez vos identifiants SMTP dans le fichier .env'
            ], 500);
        }
    }
}
