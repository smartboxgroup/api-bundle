<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

use Smartbox\ApiBundle\Annotation\JsonSchemaFile;

/**
 * Class PersonWithMissingSchemaFile
 *
 * @JsonSchemaFile("missing.schema.json")
 */
class PersonWithMissingSchemaFile
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
