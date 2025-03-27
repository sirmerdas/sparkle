<?php

namespace Sirmerdas\Sparkle\Enums;

enum ComparisonOperator: string
{
    case EQUAL = '=';
    case GREATER_THAN = '>';
    case GREATER_THAN_OR_EQUAL = '>=';
    case NOT_EQUAL = '!=';
    case NOT_EQUAL_ALT = '<>';
    case LESS_THAN = '<';
    case LESS_THAN_OR_EQUAL = '<=';
    case BETWEEN = 'BETWEEN';
    case LIKE = 'LIKE';
    case IN = 'IN';
    case ALL = 'ALL';
    case AND = 'AND';
    case ANY = 'ANY';
    case EXISTS = 'EXISTS';
    case NOT = 'NOT';
    case OR = 'OR';
    case SOME = 'SOME';
}
