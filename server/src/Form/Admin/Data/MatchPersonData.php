<?php

namespace App\Form\Admin\Data;

use App\Entity\Country;
use App\Entity\Person;

class MatchPersonData
{
    public ?Person $person = null;
    public ?string $name = null;
    public ?Country $nationality = null;
}
