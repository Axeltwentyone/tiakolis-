<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Illuminate\Support\defer;

/** Envoi des e-mails : un échec est noté dans storage/logs/laravel.log mais ne bloque jamais une commande. */
class Courrier
{
    /** L'équipe : les adresses de NOTIFICATION_EMAIL + les admins du back office qui reçoivent les e-mails. */
    public static function equipe(): array
    {
        $env = array_map('trim', explode(',', (string) config('services.precommandes.notification_email')));
        $admins = User::where('recoit_mails', true)->pluck('email')->all();

        return array_values(array_unique(array_map('mb_strtolower', array_filter([...$env, ...$admins]))));
    }

    public static function envoyerEquipe(Mailable $mail, string $contexte): void
    {
        // un e-mail par personne : personne ne voit les adresses des autres
        foreach (self::equipe() as $adresse) {
            self::envoyer($adresse, clone $mail, $contexte);
        }
    }

    public static function envoyer(string $adresse, Mailable $mail, string $contexte): void
    {
        $envoi = function () use ($adresse, $mail, $contexte) {
            try {
                Mail::to($adresse)->send($mail);
            } catch (Throwable $e) {
                Log::error('E-mail non envoyé ('.class_basename($mail).") à {$adresse} pour {$contexte}", ['erreur' => $e->getMessage()]);
            }
        };
        // sur le site : envoi APRÈS la réponse au client (il n'attend jamais le serveur mail) ; en console et en test : tout de suite
        app()->runningInConsole() ? $envoi() : defer($envoi);
    }
}
