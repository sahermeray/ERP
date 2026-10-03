<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $email = config('app.development_user_email');
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            User::factory()->make(['name' => 'Test User', 'email' => $email])->getAttributes(),
        );

        $organization = Organization::query()->firstOrCreate(
            ['slug' => 'development-office'],
            ['name' => 'Development Office'],
        );
        $organization->update(['is_active' => true]);
        $organization->users()->syncWithoutDetaching([
            $user->id => ['status' => 'active', 'joined_at' => now()],
        ]);
        $organization->users()->updateExistingPivot($user->id, ['status' => 'active']);
    }
}
