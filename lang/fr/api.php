<?php

return [
    'auth' => [
        'credentials_incorrect' => 'Les identifiants fournis sont incorrects.',
        'account_inactive' => 'Votre compte est inactif.',
        'logged_out' => 'Déconnexion réussie.',
    ],

    'orders' => [
        'service_not_available' => 'Le service n\'est pas disponible.',
        'quantity_between' => 'La quantité doit être comprise entre :min et :max.',
        'link_required' => 'Le lien est obligatoire.',
        'duplicate_target_pending' => 'Vous avez déjà une commande active pour cette cible. Attendez qu\'elle soit terminée, partielle ou annulée avant de recommander.',
        'provider_rejected' => 'Le fournisseur a rejeté la commande.',
    ],

    'refill' => [
        'not_eligible' => 'Cette commande n\'est pas éligible au refill.',
        'request_failed' => 'La demande de refill a échoué. Veuillez réessayer plus tard.',
        'submitted_pending' => 'Demande de refill envoyée. Elle est en attente.',
        'order_not_placed' => 'La commande n\'a pas été passée avec succès.',
        'service_no_refill' => 'Ce service n\'inclut pas de refill.',
        'after_completed_partial' => 'Le refill est disponible uniquement après une commande terminée ou partielle.',
        'warranty_expired' => 'Garantie refill expirée (:days jours).',
        'already_in_progress' => 'Une demande de refill est déjà en cours.',
    ],

    'deposits' => [
        'amount_gt_zero' => 'Le montant doit être supérieur à zéro.',
        'invalid_method' => 'Méthode de dépôt invalide.',
        'ccp_proof_required' => 'Une preuve de paiement est requise pour les dépôts CCP.',
        'pending_only_approve' => 'Seuls les dépôts en attente peuvent être approuvés.',
        'pending_only_reject' => 'Seuls les dépôts en attente peuvent être rejetés.',
    ],

    'checkout' => [
        'receipt_pending' => 'Reçu envoyé. En attente d\'approbation admin sur Telegram.',
        'receipt_processed' => 'Reçu envoyé et commande traitée.',
        'minimum_checkout' => 'Le montant minimum de commande est de :amount DA. Veuillez d\'abord recharger votre portefeuille.',
        'minimum_topup' => 'Le montant minimum de recharge est de :amount DA.',
    ],

    'pricing' => [
        'eur_idr_gt_zero' => 'PRICING_EUR_IDR doit être supérieur à zéro.',
        'eur_dzd_gt_zero' => 'PRICING_EUR_DZD doit être supérieur à zéro.',
        'quantity_min_one' => 'La quantité doit être d\'au moins 1.',
        'price_changed' => 'Le prix a changé. Attendu :expected DA mais le prix actuel est :actual DA. Actualisez et réessayez.',
    ],

    'wallet' => [
        'debit_positive' => 'Le montant du débit doit être positif.',
        'insufficient_balance' => 'Solde du portefeuille insuffisant.',
    ],

    'sofizpay' => [
        'disabled' => 'Le paiement Algérie Post n\'est pas disponible pour le moment.',
        'phone_required' => 'Le numéro de téléphone est requis pour le paiement Algérie Post.',
        'phone_invalid' => 'Saisissez un numéro mobile algérien valide (ex. 0555123456 ou +213555123456).',
        'missing_reference' => 'Référence de paiement manquante.',
        'missing_cib_transaction' => 'Identifiant de transaction manquant.',
        'payment_not_successful' => 'Le paiement n\'a pas abouti.',
    ],

    'comments' => [
        'required_for_service' => 'Les commentaires sont obligatoires pour ce service (un par ligne).',
        'enter_at_least_one' => 'Saisissez au moins un commentaire (un par ligne).',
        'count_mismatch' => '{0} Vous avez saisi :count commentaires mais la quantité est :quantity. Ils doivent correspondre.|{1} Vous avez saisi :count commentaire mais la quantité est :quantity. Ils doivent correspondre.|[2,*] Vous avez saisi :count commentaires mais la quantité est :quantity. Ils doivent correspondre.',
    ],

    'catalog' => [
        'quality' => [
            'premium' => 'Premium',
            'standard' => 'Standard',
            'economy' => 'Économique',
        ],
        'refill' => [
            'auto' => 'Refill auto',
            'auto_days' => 'Refill auto :days j',
            'manual' => 'Refill',
            'manual_days' => 'Refill :days j',
            'lifetime' => 'Refill à vie',
            'none' => 'Sans refill',
        ],
        'start' => [
            'instant' => 'Instantané',
            'fast' => 'Rapide',
            'slow' => 'Lent',
            'normal' => 'Démarrage normal',
        ],
        'drip_feed' => 'Drip-feed',
        'top' => 'Top',
        'cheap' => 'Économique',
    ],
];
