<?php

namespace Mryup\HyperfMongodb\Aggregates;

use Mryup\HyperfMongodb\IgnoreNotFoundProperty;

/**
 * @property $from
 * @property $localField
 * @property $foreignField
 * @property $relatedUser
 */
class Lookup
{
    use IgnoreNotFoundProperty;
}