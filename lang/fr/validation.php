<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'integer' => 'Le champ :attribute doit être au moins :min.',
    ],
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'file' => 'Le champ :attribute ne doit pas dépasser :max kilo-octets.',
    ],
    'uuid' => 'Le champ :attribute doit être un UUID valide.',
    'exists' => 'Le :attribute sélectionné est invalide.',
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'file' => 'Le champ :attribute doit être un fichier.',

    'attributes' => [
        'service_id' => 'service',
        'link' => 'lien',
        'quantity' => 'quantité',
        'amount_dzd' => 'montant',
        'receipt' => 'reçu',
        'comments' => 'commentaires',
        'idempotency_key' => 'clé d\'idempotence',
        'expected_charge_dzd' => 'montant attendu',
        'reference' => 'référence',
    ],
];
