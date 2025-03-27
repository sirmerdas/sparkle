<?php

namespace Sirmerdas\Sparkle\Enums;

enum JoinType: string
{
    case INNER = 'INNER';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
    case FULL_OUTER = 'FULL OUTER';
    case CROSS = 'CROSS';
    case SELF = 'SELF';
}
