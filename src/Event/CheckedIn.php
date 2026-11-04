<?php

namespace Primexcalibur\CheckinWithHistory\Event;

use Flarum\User\User;
use Primexcalibur\CheckinWithHistory\Model\CheckinRecord;

class CheckedIn
{
    public function __construct(
        public User $user,
        public CheckinRecord $record,
        public int $supplementCount = 0
    ) {
    }
}
