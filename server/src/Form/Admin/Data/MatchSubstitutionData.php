<?php

namespace App\Form\Admin\Data;

use App\Entity\Player;

class MatchSubstitutionData
{
    public string $teamRole = 'A';
    public ?Player $playerOut = null;
    public ?string $playerOutName = null;
    public ?Player $playerIn = null;
    public ?string $playerInName = null;
    public ?string $minute = null;
}
