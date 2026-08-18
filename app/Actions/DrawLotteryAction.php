<?php

namespace App\Actions;

use App\Models\LotteryPool;
use App\Models\LotteryWinner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DrawLotteryAction
{
    public function execute(LotteryPool $pool, string $prize, User $officer): LotteryWinner
    {
        return DB::transaction(function () use ($pool, $prize, $officer) {
            $alreadyWonIds = $pool->winners()->pluck('participant_id');

            $eligible = $pool->participants()
                ->whereNotIn('participants.id', $alreadyWonIds)
                ->inRandomOrder()
                ->lockForUpdate()
                ->first();

            if (! $eligible) {
                throw new RuntimeException('Tidak ada lagi peserta yang eligible untuk diundi di pool ini.');
            }

            return LotteryWinner::create([
                'lottery_pool_id' => $pool->id,
                'participant_id' => $eligible->id,
                'prize' => $prize,
                'drawn_at' => now(),
                'drawn_by' => $officer->id,
            ]);
        });
    }
}
