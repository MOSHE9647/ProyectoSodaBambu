<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNull('deleted_at')
            ->whereRoles(UserRole::ADMIN->value)
            ->take(10)
            ->get();
        $clients = Client::whereNull('deleted_at')
            ->take(10)
            ->get();
        $products = Product::whereNull('deleted_at')
            ->take(10)
            ->get();

        if ($users->count() < 10) {
            $users = $users->merge(User::factory()
                ->withRole(UserRole::ADMIN)
                ->count($users->count() > 0 ? 10 - $users->count() : 10)
                ->create()
            );
        }

        if ($clients->count() < 10) {
            $clients = $clients->merge(Client::factory()
                ->count($clients->count() > 0 ? 10 - $clients->count() : 10)
                ->create()
            );
        }

        if ($products->count() < 10) {
            $products = $products->merge(Product::factory()
                ->count($products->count() > 0 ? 10 - $products->count() : 10)
                ->create()
            );
        }

        $clientIds = $clients->pluck('id')->toArray();
        $productIds = Product::pluck('id')->toArray();

        $users->each(function (User $user) use ($productIds, $clientIds) {
            $randomProductId = $productIds[array_rand($productIds)];
            $randomClientId = $clientIds[array_rand($clientIds)];

            $contract = Contract::factory()->for($user)->create([
                'client_id' => $randomClientId,
            ]);

            $contract->details()->save(
                ContractDetail::factory()->make([
                    'contract_id' => $contract->id,
                    'product_id' => $randomProductId,
                ])
            );
        });
    }
}
