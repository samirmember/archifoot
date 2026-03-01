<?php

namespace App\Form\Admin\Data;

use App\Entity\Player;

class MatchGoalData
{
    public string $teamRole = 'A';
    public ?Player $scorer = null;
    public ?string $scorerName = null;
    public ?string $minute = null;
    public ?string $goalType = null;
}
