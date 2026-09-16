<?php

return [
    'required' => 'Câmpul :attribute este obligatoriu.',
    'string' => 'Câmpul :attribute trebuie să conțină text.',
    'email' => 'Introdu o adresă de email validă.',
    'max' => ['string' => 'Câmpul :attribute poate avea cel mult :max caractere.'],
    'min' => ['string' => 'Câmpul :attribute trebuie să aibă cel puțin :min caractere.'],
    'confirmed' => 'Confirmarea câmpului :attribute nu corespunde.',
    'unique' => 'Această valoare pentru :attribute este deja folosită.',
    'in' => 'Valoarea aleasă pentru :attribute nu este validă.',
    'boolean' => 'Valoarea pentru :attribute nu este validă.',
    'current_password' => 'Parola nu este corectă.',
    'attributes' => [
        'name' => 'nume', 'email' => 'email', 'password' => 'parolă',
        'account_type' => 'tipul contului', 'token' => 'link de recuperare', 'remember' => 'ține-mă minte',
    ],
];
