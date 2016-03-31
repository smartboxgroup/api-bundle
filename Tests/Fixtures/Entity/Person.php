<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

/**
 * Class Person
 *
 * @Smartbox\ApiBundle\Annotation\JsonSchema("http://example.com/schemas/person.schema.json")
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

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return Person
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Person
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * @param string $surname
     *
     * @return Person
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;
        return $this;
    }

    /**
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * @param string $birthDate
     *
     * @return Person
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return Person
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return int
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @param int $credit
     *
     * @return Person
     */
    public function setCredit($credit)
    {
        $this->credit = $credit;
        return $this;
    }
}
