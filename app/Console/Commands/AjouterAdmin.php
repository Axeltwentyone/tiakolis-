<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/** Crée un compte du back office, ou change le mot de passe d'un compte existant (même e-mail). */
class AjouterAdmin extends Command
{
    protected $signature = 'admin:ajouter';

    protected $description = 'Ajouter un compte au back office (ou changer son mot de passe)';

    public function handle(): int
    {
        $email = text('E-mail', required: true, validate: fn ($v) => Validator::make(['e' => $v], ['e' => 'email'])->fails() ? 'Adresse e-mail invalide.' : null);
        $existant = User::where('email', $email)->first();
        $nom = text('Prénom', default: $existant?->name ?? '', required: true);
        $mdp = password('Mot de passe (10 caractères minimum)', required: true, validate: fn ($v) => mb_strlen($v) < 10 ? 'Trop court : 10 caractères minimum.' : null);
        if (password('Confirme le mot de passe', required: true) !== $mdp) {
            $this->error('Les deux mots de passe ne correspondent pas.');

            return self::FAILURE;
        }

        $mails = confirm('Recevoir les e-mails de commandes et de paiements ?', default: $existant?->recoit_mails ?? true, yes: 'Oui', no: 'Non');

        User::updateOrCreate(['email' => $email], ['name' => $nom, 'password' => $mdp, 'recoit_mails' => $mails]);
        $this->info($existant ? "Mot de passe de {$email} mis à jour." : "Compte créé : {$email} peut se connecter sur ".url('/admin'));
        $this->table(['Compte', 'Prénom', 'Reçoit les e-mails'], User::orderBy('name')->get()->map(fn ($u) => [$u->email, $u->name, $u->recoit_mails ? 'oui' : 'non']));

        return self::SUCCESS;
    }
}
