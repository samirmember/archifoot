<?php

namespace App\Form\Admin\Model;

use App\Entity\Player;
use App\Entity\Team;

class SubstitutionInput
{
    public ?Team $team = null;
    public ?Player $playerOut = null;
    public ?string $newPlayerOutName = null;
    public ?Player $playerIn = null;
    public ?string $newPlayerInName = null;
    public ?string $minute = null;
}
