<?php
namespace App\Service;

enum FixtureOutcomeEnum: int
{
    case LOSER = 0; // perdant
    case WINNER = 1; // gagnant
    case DRAW = 2; // nul

    public function label(): string
    {
        return match ($this) {
            self::LOSER  => 'perdant',
            self::WINNER => 'gagnant',
            self::DRAW   => 'nul',
        };
    }
}
