<?php

namespace App\Form\Admin\Model;

use App\Entity\Player;
use App\Entity\Team;

class GoalInput
{
    public ?Team $team = null;
    public ?Player $scorer = null;
    public ?string $newScorerName = null;
    public ?string $minute = null;
    public ?string $goalType = null;
}
