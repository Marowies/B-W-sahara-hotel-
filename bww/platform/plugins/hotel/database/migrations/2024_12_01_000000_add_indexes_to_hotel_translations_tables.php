<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // table => [fk_column, fk_index_name, composite_index_name]
        $tables = [
            'ht_amenities_translations' => ['ht_amenities_id', 'idx_ht_amenities_trans_fk', 'idx_ht_amenities_trans_fk_lang'],
            'ht_features_translations' => ['ht_features_id', 'idx_ht_features_trans_fk', 'idx_ht_features_trans_fk_lang'],
            'ht_food_types_translations' => ['ht_food_types_id', 'idx_ht_food_types_trans_fk', 'idx_ht_food_types_trans_fk_lang'],
            'ht_foods_translations' => ['ht_foods_id', 'idx_ht_foods_trans_fk', 'idx_ht_foods_trans_fk_lang'],
            'ht_places_translations' => ['ht_places_id', 'idx_ht_places_trans_fk', 'idx_ht_places_trans_fk_lang'],
            'ht_room_categories_translations' => ['ht_room_categories_id', 'idx_ht_room_cat_trans_fk', 'idx_ht_room_cat_trans_fk_lang'],
            'ht_rooms_translations' => ['ht_rooms_id', 'idx_ht_rooms_trans_fk', 'idx_ht_rooms_trans_fk_lang'],
            'ht_services_translations' => ['ht_services_id', 'idx_ht_services_trans_fk', 'idx_ht_services_trans_fk_lang'],
        ];

        foreach ($tables as $table => $config) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            [$foreignKey, $fkIndex, $compositeIndex] = $config;

            Schema::table($table, function (Blueprint $blueprint) use ($foreignKey, $fkIndex, $compositeIndex) {
                $blueprint->index($foreignKey, $fkIndex);
                $blueprint->index([$foreignKey, 'lang_code'], $compositeIndex);
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'ht_amenities_translations' => ['idx_ht_amenities_trans_fk', 'idx_ht_amenities_trans_fk_lang'],
            'ht_features_translations' => ['idx_ht_features_trans_fk', 'idx_ht_features_trans_fk_lang'],
            'ht_food_types_translations' => ['idx_ht_food_types_trans_fk', 'idx_ht_food_types_trans_fk_lang'],
            'ht_foods_translations' => ['idx_ht_foods_trans_fk', 'idx_ht_foods_trans_fk_lang'],
            'ht_places_translations' => ['idx_ht_places_trans_fk', 'idx_ht_places_trans_fk_lang'],
            'ht_room_categories_translations' => ['idx_ht_room_cat_trans_fk', 'idx_ht_room_cat_trans_fk_lang'],
            'ht_rooms_translations' => ['idx_ht_rooms_trans_fk', 'idx_ht_rooms_trans_fk_lang'],
            'ht_services_translations' => ['idx_ht_services_trans_fk', 'idx_ht_services_trans_fk_lang'],
        ];

        foreach ($tables as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                $blueprint->dropIndex($indexes[0]);
                $blueprint->dropIndex($indexes[1]);
            });
        }
    }
};
