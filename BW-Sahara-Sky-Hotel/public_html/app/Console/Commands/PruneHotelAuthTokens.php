<?php

namespace App\Console\Commands;

use App\Services\HotelTokenService;
use Illuminate\Console\Command;

class PruneHotelAuthTokens extends Command
{
    protected $signature = 'hotel:prune-tokens';

    protected $description = 'Delete expired and long-revoked customer refresh tokens';

    public function handle(HotelTokenService $tokens): int
    {
        $this->info($tokens->prune() . ' token rows deleted.');

        return self::SUCCESS;
    }
}
