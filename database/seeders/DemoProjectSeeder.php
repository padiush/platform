<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use App\Services\ExampleStudy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The demonstration study, used for the public-site screenshots and for demos,
 * owned by a fixture account. The study itself is built by ExampleStudy, the
 * same one any user can open as an example project.
 *
 * Run it explicitly; it is deliberately not part of DatabaseSeeder:
 *
 *   php artisan db:seed --class=DemoProjectSeeder
 */
class DemoProjectSeeder extends Seeder
{
    private const PROJECT_NAME = 'Plantas útiles de la cordillera (estudio demostrativo)';

    private const DEMO_EMAIL = 'demo@padiush.test';

    /**
     * A fixture credential for a fixture account, so the screenshot capture
     * script can sign in the way a person would. Harmless because this seeder
     * refuses to run in production and the account exists nowhere else.
     */
    private const DEMO_PASSWORD = 'demo-screenshots';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoProjectSeeder refuses to run in production.');

            return;
        }

        // Re-running should replace the demo, never accumulate copies of it.
        Project::where('name', self::PROJECT_NAME)->get()->each->delete();

        // updateOrCreate, not firstOrCreate: re-running the seeder should leave
        // the fixture in a known state rather than keeping whatever password an
        // earlier run happened to set.
        $user = User::updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'Equipo Padiush',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        // In Spanish, the language the screenshots are taken in.
        app(ExampleStudy::class)->build(
            $user,
            'es',
            example: false,
            name: self::PROJECT_NAME,
            institution: 'Proyecto demostrativo',
        );

        $this->command?->info('Demo project seeded: '.self::PROJECT_NAME);
    }
}
