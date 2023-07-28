<?php

/**
 * @property int $id
 * @schemaIgnore
 */
class Book extends LeanMapper\Entity
{
}


/**
 * @property int $id
 * @property string $text m:schemaIgnore
 */
class Author extends LeanMapper\Entity
{
}
