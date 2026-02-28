<?php

namespace App\Form\Admin\Model;

use App\Entity\Player;
use App\Entity\Position;
use App\Entity\Team;

class LineupInput
{
    public ?Team $team = null;
    public ?Player $player = null;
    public ?string $newPlayerName = null;
    public ?int $shirtNumber = null;
    public ?string $lineupRole = null;
    public ?bool $captain = false;
    public ?Position $position = null;
    public ?int $sortOrder = null;
}
