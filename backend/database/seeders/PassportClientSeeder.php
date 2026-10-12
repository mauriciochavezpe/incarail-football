<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

/**
 * Crea el "personal access client" de Passport que emite los tokens del login.
 * Así no hace falta un paso manual (passport:client --personal) en el README.
 */
class PassportClientSeeder extends Seeder
{
    public function run(ClientRepository $clients): void
    {
        $exists = Client::query()
            ->where('revoked', false)
            ->get()
            ->contains(fn (Client $client) => in_array('personal_access', $client->grant_types ?? [], true));

        if (! $exists) {
            $clients->createPersonalAccessGrantClient('SPA Personal Access Client', 'users');
        }
    }
}
