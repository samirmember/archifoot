<?php

namespace App\Form\Admin\Data;

use App\Entity\Player;
use App\Entity\Position;

class MatchLineupData
{
    public string $teamRole = 'A';
    public string $lineupRole = 'STARTER';
    public ?Player $player = null;
    public ?string $playerName = null;
    public ?int $shirtNumber = null;
    public bool $isCaptain = false;
    public ?Position $position = null;
}
