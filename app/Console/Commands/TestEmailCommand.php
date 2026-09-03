<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {email? : L\'adresse email destinataire}';
    protected $description = 'Envoie un email de test pour vérifier la configuration SMTP (Brevo)';

    public function handle()
    {
        $email = $this->argument('email') ?? 'adjimelinklaanambo@gmail.com';

        $this->info("Tentative d'envoi d'un email de test à : {$email}");

        try {
            Mail::raw('Ceci est un test de connexion SMTP pour StageLink ! 🚀', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test SMTP - StageLink')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->info("✅ Email de test envoyé avec succès !");
            $this->line("Détails :");
            $this->line("- From : " . config('mail.from.address'));
            $this->line("- Mailer : " . config('mail.default'));

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Échec de l'envoi : " . $e->getMessage());
            $this->warn("Conseil : Vérifiez vos identifiants SMTP (BREVO_API_KEY, etc.) dans le fichier .env");

            return 1;
        }
    }
}
