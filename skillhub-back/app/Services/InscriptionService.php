<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InscriptionService
{
    public const LIMITE_INSCRIPTIONS_ACTIVES = 5;

    public function countActiveInscriptionsForUser(int $userId): int
    {
        return (int) DB::table('inscription')
            ->where('idUtilisateur', $userId)
            ->count();
    }

    public function hasReachedActiveInscriptionsLimit(int $userId): bool
    {
        return $this->countActiveInscriptionsForUser($userId) >= self::LIMITE_INSCRIPTIONS_ACTIVES;
    }
}
