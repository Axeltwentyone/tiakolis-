<?php

namespace App\Enums;

/** Cycle de vie d'une commande. Une commande annulée rend ses pièces au stock. */
enum Statut: string
{
    case EnAttente = 'en_attente';   // commande passée, paiement Wave pas encore reçu
    case AVerifier = 'a_verifier';   // le client a envoyé sa capture : à vérifier dans Wave
    case Payee = 'payee';
    case Livree = 'livree';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'Attente paiement',
            self::AVerifier => 'Capture à vérifier',
            self::Payee => 'Payée',
            self::Livree => 'Livrée',
            self::Annulee => 'Annulée',
        };
    }

    /** Pastille du back office (classes Tailwind, couleurs de la marque). */
    public function classes(): string
    {
        return match ($this) {
            self::EnAttente => 'bg-nuit/10 text-nuit',
            self::AVerifier => 'bg-ocre text-nuit',
            self::Payee => 'bg-nuit text-ocre',
            self::Livree => 'bg-terre text-creme',
            self::Annulee => 'bg-rouge/10 text-rouge line-through',
        };
    }
}
