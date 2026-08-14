<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;

class TestEmailController extends Controller
{
    public function sendTestEmail()
    {
        try {
            $email = 'votre.email@test.com'; // Remplacez par votre email

            Mail::raw('Ceci est un test de Brevo ! 🚀', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Brevo - StageLink');
            });

            return response()->json([
                'message' => 'Email envoyé avec succès !',
                'to' => $email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}