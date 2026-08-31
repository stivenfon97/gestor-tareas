<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * No se usa `WithoutModelEvents`: los observers son los que asignan el
     * código correlativo y escriben el historial de estados. Silenciarlos
     * dejaría la base sembrada con datos que el resto del sistema no puede
     * producir.
     */
    public function run(): void
    {
        $this->call(RolesYPermisosSeeder::class);
        $this->call(DesarrolloSeeder::class);
    }
}
