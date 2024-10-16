<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Kasikorn Bank (K plus)',
                'image' => 'KPLUS.png',
            ],
            [
                'name' => 'Siam Commercial Bank (SCB)',
                'image' => 'SCB.png',
            ],
            [
                'name' => 'TMBTHANACHART BANK (TTB)',
                'image' => 'TTB.png',
            ],
            [
                'name' => 'Krungthai Bank (KTB)',
                'image' => 'KTB.png',
            ],
            [
                'name' => 'Bangkok Bank(BBL)',
                'image' => 'BBL.png',
            ],
            [
                'name' => 'United Overseas Bank(UOB)',
                'image' => 'UOB.png',
            ],
            [
                'name' => 'Kiatnakin Phatra Bank(KKP)',
                'image' => 'KKP.png',
            ],
            [
                'name' => 'Government Savings Bank(GSB)',
                'image' => 'GSB.png',
            ],
            [
                'name' => 'Bank of Ayudhya (BAY)',
                'image' => 'BAY.png',
            ],
            [
                'name' => 'Bank for Agriculture and Agricultural Cooperatives(BBAC)',
                'image' => 'BBAC.png',
            ],
            [
                'name' => 'Commerce International Merchant Bank(CIMB)',
                'image' => 'CIMB.png',
            ],
            [
                'name' => 'True Money Wallet',
                'image' => 'truemoney.png',
            ],
        ];

        DB::table('payment_types')->insert($types);
    }
}
