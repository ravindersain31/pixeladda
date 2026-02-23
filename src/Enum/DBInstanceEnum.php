<?php

namespace App\Enum;

enum DBInstanceEnum: string
{
    case DEFAULT = 'default';
    case REPLICA = 'replica';
    case READER = 'reader';

}
