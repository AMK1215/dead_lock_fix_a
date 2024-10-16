<?php

namespace Database\Seeders;

use App\Models\Admin\GameType;
use App\Models\Admin\GameTypeProduct;
use Illuminate\Database\Seeder;

class GameTypeProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'product_id' => 1,
                'game_type_id' => 1,
                'image' => 'pragmatic_play.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 1,
                'game_type_id' => 2,
                'image' => 'pragmatic_casino.png',
                'rate' => '1.0000',
            ],
            //  [
            //     'product_id' => 1,
            //     'game_type_id' => 4,
            //     'image' => 'pragmatic_casino.png',
            //     'rate' => '1.0000',
            // ],
            // [
            //     'product_id' => 1,
            //     'game_type_id' => 6,
            //     'image' => 'pragmatic_casino.png',
            //     'rate' => '1.0000',
            // ],
            // [
            //     'product_id' => 1,
            //     'game_type_id' => 7,
            //     'image' => 'pragmatic_casino.png',
            //     'rate' => '1.0000',
            // ],
            [
                'product_id' => 2,
                'game_type_id' => 3,
                'image' => 'sbo_sport.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 3,
                'game_type_id' => 1,
                'image' => 'joker.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 3,
                'game_type_id' => 4,
                'image' => 'joker_fishing.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 4,
                'game_type_id' => 2,
                'image' => 'yee_bet.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 5,
                'game_type_id' => 2,
                'image' => 'wm_casino.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 6,
                'game_type_id' => 1,
                'image' => 'yggdrasil.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 7,
                'game_type_id' => 1,
                'image' => 'spade_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 7,
                'game_type_id' => 4,
                'image' => 'spade_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 8,
                'game_type_id' => 2,
                'image' => 'vivo_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 9,
                'game_type_id' => 1,
                'image' => 'playstar.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 9,
                'game_type_id' => 4,
                'image' => 'playstar.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 10,
                'game_type_id' => 1,
                'image' => 'true_lab.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 11,
                'game_type_id' => 1,
                'image' => 'bgaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 12,
                'game_type_id' => 1,
                'image' => 'wazan.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 13,
                'game_type_id' => 1,
                'image' => 'Fazi.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 14,
                'game_type_id' => 1,
                'image' => 'play_pearls.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 15,
                'game_type_id' => 1,
                'image' => 'netgame.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 16,
                'game_type_id' => 5,
                'image' => 'Kiron-Logo-Flat.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 17,
                'game_type_id' => 1,
                'image' => 'logo_redrake.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 18,
                'game_type_id' => 1,
                'image' => 'bcoongo.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 19,
                'game_type_id' => 1,
                'image' => 'skywind_group.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 20,
                'game_type_id' => 1,
                'image' => 'j_d_b.png',
                'rate' => '100.0000',
            ],
            [
                'product_id' => 21,
                'game_type_id' => 1,
                'image' => 'genesis.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 22,
                'game_type_id' => 1,
                'image' => 'funta_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 23,
                'game_type_id' => 1,
                'image' => 'felix_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 24,
                'game_type_id' => 1,
                'image' => 'zeus_play.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 25,
                'game_type_id' => 1,
                'image' => 'ka_gaming.png',
                'rate' => '1.0000',
            ],

            [
                'product_id' => 26,
                'game_type_id' => 1,
                'image' => 'ne_tent.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 27,
                'game_type_id' => 1,
                'image' => 'gaming_world.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 28,
                'game_type_id' => 1,
                'image' => 'asia_gaming.png',
                'rate' => '100.0000',
            ],
            [
                'product_id' => 28,
                'game_type_id' => 2,
                'image' => 'asia_gaming_casino.png',
                'rate' => '100.0000',
            ],
            [
                'product_id' => 29,
                'game_type_id' => 2,
                'image' => 'evolution_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 30,
                'game_type_id' => 1,
                'image' => 'big_gaming.png',
                'rate' => '1000.0000',
            ],
            [
                'product_id' => 30,
                'game_type_id' => 2,
                'image' => 'big_gaming_casino.png',
                'rate' => '1000.0000',
            ],
            [
                'product_id' => 31,
                'game_type_id' => 1,
                'image' => 'pg_soft.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 32,
                'game_type_id' => 1,
                'image' => 'cq_9.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 32,
                'game_type_id' => 4,
                'image' => 'cq_9_fishing.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 33,
                'game_type_id' => 2,
                'image' => 'sexy_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 34,
                'game_type_id' => 1,
                'image' => 'real_time_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 35,
                'game_type_id' => 1,
                'image' => 'jili.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 36,
                'game_type_id' => 2,
                'image' => 'king_855.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 37,
                'game_type_id' => 1,
                'image' => 'habanero.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 38,
                'game_type_id' => 1,
                'image' => 'live22sm.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 39,
                'game_type_id' => 1,
                'image' => 'yes_get_rich.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 40,
                'game_type_id' => 1,
                'image' => 'simple_play.png',
                'rate' => '1000.0000',
            ],

            [
                'product_id' => 41,
                'game_type_id' => 1,
                'image' => 'advant_play.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 42,
                'game_type_id' => 3,
                'image' => 'ssport.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 43,
                'game_type_id' => 2,
                'image' => 'dream_gaming.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 44,
                'game_type_id' => 1,
                'image' => 'mr_slottry.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 45,
                'game_type_id' => 1,
                'image' => 'smart_soft.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 46,
                'game_type_id' => 1,
                'image' => 'evoplay.png',
                'rate' => '1.0000',
            ],
        ];

        GameTypeProduct::insert($data);
    }
}
