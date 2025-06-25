<?php

declare(strict_types=1);

/**
 * @property int $id
 * @property string $name
 * @property Tag[] $tags m:hasMany
 */
class Book extends \LeanMapper\Entity
{
}
