<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

use Smartbox\ApiBundle\Annotation\JsonSchemaFile;

/**
 * Class Person
 *
 * @JsonSchemaFile("person.schema.json")
 */
class Person
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
