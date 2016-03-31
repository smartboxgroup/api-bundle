<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

use Smartbox\ApiBundle\Annotation\JsonSchemaFile;

/**
 * Class PersonWithInvalidSchemaFile
 *
 * @JsonSchemaFile("invalid-schema.txt")
 */
class PersonWithInvalidSchemaFile
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $surname;

    /** @var string */
    protected $birthDate;

    /** @var string */
    protected $password;

    /** @var int */
    protected $credit;
}
