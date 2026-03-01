<?php

namespace App\Form\Admin\Model;

use App\Entity\Country;
use App\Entity\Person;
use App\Entity\Team;

class StaffInput
{
    public ?Team $team = null;
    public ?string $role = null;
    public ?Person $person = null;
    public ?string $newFullName = null;
    public ?Country $nationality = null;
}
