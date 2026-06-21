<?php

use App\Support\SiteVariables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_settings', 'site_variables')) {
                $table->json('site_variables')->nullable()->after('dashboard_notes');
            }
        });

        DB::table('site_settings')
            ->orderBy('id')
            ->each(function (object $settings): void {
                $variables = SiteVariables::normalizeRows(json_decode((string) ($settings->site_variables ?? '[]'), true) ?: []);

                $variables = $this->mergeVariable(
                    $variables,
                    'Address',
                    'address',
                    $settings->address ?? null,
                );

                $variables = $this->mergeVariable(
                    $variables,
                    'Service Times',
                    'service-times',
                    $settings->sunday_service_times ?? null,
                );

                DB::table('site_settings')
                    ->where('id', $settings->id)
                    ->update([
                        'site_variables' => $variables === [] ? null : json_encode($variables),
                    ]);
            });

        foreach (['sunday_service_times', 'address', 'office_hours'] as $column) {
            if (Schema::hasColumn('site_settings', $column)) {
                Schema::table('site_settings', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_settings', 'sunday_service_times')) {
                $table->text('sunday_service_times')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'address')) {
                $table->text('address')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'office_hours')) {
                $table->text('office_hours')->nullable();
            }
        });

        DB::table('site_settings')
            ->orderBy('id')
            ->each(function (object $settings): void {
                $variables = collect(SiteVariables::normalizeRows(json_decode((string) ($settings->site_variables ?? '[]'), true) ?: []))
                    ->keyBy('variable');

                DB::table('site_settings')
                    ->where('id', $settings->id)
                    ->update([
                        'address' => $variables->get('address')['value'] ?? null,
                        'sunday_service_times' => $variables->get('service-times')['value'] ?? null,
                    ]);
            });

        Schema::table('site_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('site_settings', 'site_variables')) {
                $table->dropColumn('site_variables');
            }
        });
    }

    /**
     * @param  array<int, array{name: string, variable: string, value: string}>  $variables
     * @return array<int, array{name: string, variable: string, value: string}>
     */
    private function mergeVariable(array $variables, string $name, string $variable, mixed $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $variables;
        }

        $variable = SiteVariables::normalizeKey($variable);

        if (collect($variables)->contains(fn (array $row): bool => $row['variable'] === $variable)) {
            return $variables;
        }

        $variables[] = [
            'name' => $name,
            'variable' => $variable,
            'value' => $value,
        ];

        return $variables;
    }
};
