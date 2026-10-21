<?php

namespace App\Helpers;

use App\Models\User;

class EmailHelper
{
    public static function generate($nama, $nip = null)
    {
        $base = strtolower($nama);
        $base = preg_replace('/[^a-z0-9\s]/', '', $base);
        $base = preg_replace('/\s+/', '.', trim($base));

        $email = $base . '@email.unikom.ac.id';

        if (User::where('email', $email)->exists()) {
            if ($nip) {
                return $nip . '@email.unikom.ac.id';
            }

            $counter = 1;
            while (User::where('email', $email)->exists()) {
                $email = $base . $counter . '@email.unikom.ac.id';
                $counter++;
            }
        }

        return $email;
    }
}