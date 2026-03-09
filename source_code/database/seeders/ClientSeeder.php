<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Manually create a few clients with specific data
        $clients = [
            ["first_name" => "Juan", "last_name" => "Pérez", "email" => "juan.perez@example.com", "phone" => "+506 5896 1234"],
            ["first_name" => "María", "last_name" => "Gómez", "email" => "maria.gomez@example.com", "phone" => "+506 2222 5678"],
            ["first_name" => "Carlos", "last_name" => "Rodríguez", "email" => "carlos.rodriguez@example.com", "phone" => "+506 8888 9012"],
            ["first_name" => "Ana", "last_name" => "López", "email" => "ana.lopez@example.com", "phone" => "+506 1111 3456"],
            ["first_name" => "Luis", "last_name" => "Martínez", "email" => "luis.martinez@example.com", "phone" => "+506 3333 7890"],
            ["first_name" => "Sofía", "last_name" => "Hernández", "email" => "sofia.hernandez@example.com", "phone" => "+506 4444 1234"],
            ["first_name" => "Miguel", "last_name" => "García", "email" => "miguel.garcia@example.com", "phone" => "+506 6666 5432"],
            ["first_name" => "Laura", "last_name" => "Sánchez", "email" => "laura.sanchez@example.com", "phone" => "+506 7777 9876"],
            ["first_name" => "Diego", "last_name" => "Ramírez", "email" => "diego.ramirez@example.com", "phone" => "+506 9999 3456"],
            ["first_name" => "Valentina", "last_name" => "Fernández", "email" => "valentina.fernandez@example.com", "phone" => "+506 1111 5678"],
        ];

        // Insert clients into the database
        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
