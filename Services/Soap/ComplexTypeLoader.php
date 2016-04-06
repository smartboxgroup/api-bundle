<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapBundle\ServiceDefinition as Definition;
use BeSimple\SoapBundle\ServiceDefinition\ComplexType;
use BeSimple\SoapBundle\Util\Collection;
use JMS\Serializer\Exclusion\VersionExclusionStrategy;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\CoreBundle\Type\Entity;
use Symfony\Component\Validator\Validator;

/**
 * Class ComplexTypeLoader
 */
class ComplexTypeLoader extends Definition\Loader\AnnotationClassLoader
{
    const ANNOTATION_TYPE = 'annotation_complextype';

    protected $aliasClass                = 'BeSimple\SoapBundle\ServiceDefinition\Annotation\Alias';
    protected $complexTypeClass          = 'BeSimple\SoapBundle\ServiceDefinition\Annotation\ComplexType';
    protected $jmsTypeClass              = 'JMS\Serializer\Annotation\Type';
    protected $symfonyValidationNotBlank = 'Symfony\Component\Validator\Constraints\NotBlank';
    protected $symfonyValidationNotNull  = 'Symfony\Component\Validator\Constraints\NotNull';

    /** @var  Serializer */
    protected $serializer;

    /**
     * @return Serializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param Serializer $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    public function load($data, $type = null)
    {
        if (!$this->isResourceDefined($data)) {
            throw new \InvalidArgumentException('The resource is not defined correctly');
        }

        $className = $data['phpType'];
        $group     = $data['group'];
        $version   = $data['version'];

        if (null === $version) {
            throw new \InvalidArgumentException('Version can not be "null"');
        }

        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $className));
        }

        $annotations = [];

        $class = new \ReflectionClass($className);
        if ($alias = $this->reader->getClassAnnotation($class, $this->aliasClass)) {
            $annotations['alias'] = $alias->getValue();
        }

        $jmsClassMeta = $this->getSerializer()->getMetadataFactory()->getMetadataForClass($className);
        $annotations['properties'] = new Collection('getName', 'BeSimple\SoapBundle\ServiceDefinition\ComplexType');

        foreach ($class->getProperties() as $property) {
            // Fetch NotBlank / NotNull from validation
            $notBlank = $this->reader->getPropertyAnnotation($property, $this->symfonyValidationNotBlank);
            if ($notBlank && $notBlank->groups && !in_array(Entity::GROUP_DEFAULT, $notBlank->groups)) {
                $notBlank = in_array($group, $notBlank->groups);
            }

            $notNull = $this->reader->getPropertyAnnotation($property, $this->symfonyValidationNotNull);
            if ($notNull && $notNull->groups && !in_array(Entity::GROUP_DEFAULT, $notNull->groups)) {
                $notNull = in_array($group, $notNull->groups);
            }

            $isNullable = !$notBlank && !$notNull;
            $soapType   = null;
            $inGroup    = true;
            $inVersion  = true;

            // JMS Metadata
            if (array_key_exists($property->name, $jmsClassMeta->propertyMetadata)) {
                /** @var PropertyMetadata $jmsPropertyMeta */
                $jmsPropertyMeta = $jmsClassMeta->propertyMetadata[$property->name];

                // Fetch JMS type
                $jmsType = $jmsPropertyMeta->type;

                // TODO: Make this more robust/general
                $confTypeName = $jmsType['name'];

                if ($jmsType['name'] == 'array') {
                    $confTypeName = $jmsType['params'][0]['name'].ApiConfigurator::$arraySymbol;
                }

                $soapType  = ApiConfigurator::getSoapTypeFor($confTypeName);
                $inGroup   = $this->isPropertyInGroup($jmsPropertyMeta, $group);
                $inVersion = $this->isPropertyInVersion($jmsPropertyMeta, $version);
            }

            // Check if the complex type is explicitly defined
            $complexType = $this->reader->getPropertyAnnotation($property, $this->complexTypeClass);

            if ($complexType && $inGroup && $inVersion) {
                $propertyComplexType = new ComplexType();
                $propertyComplexType->setValue($complexType->getValue());
                $propertyComplexType->setNillable($complexType->isNillable());
                $propertyComplexType->setName($property->getName());
                $annotations['properties']->add($propertyComplexType);
            } elseif ($soapType && $inGroup && $inVersion) {
                $propertyComplexType = new ComplexType();
                $propertyComplexType->setValue($soapType);
                $propertyComplexType->setNillable($isNullable);
                $propertyComplexType->setName($property->getName());
                $annotations['properties']->add($propertyComplexType);
            }
        }

        return $annotations;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type The resource type
     *
     * @return Boolean True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return $this->isResourceDefined($resource) && self::ANNOTATION_TYPE === $type;
    }

    /**
     * Check if the resource is defined correctly.
     *
     * @param mixed $resource A resource.
     *
     * @return boolean True if the resource is defined correctly, false otherwise.
     */
    private function isResourceDefined($resource)
    {
        return is_array($resource)
            && array_key_exists('phpType', $resource)
            && array_key_exists('group', $resource)
            && array_key_exists('version', $resource);
    }

    /**
     * Check if the property is defined for a specific group.
     *
     * @param PropertyMetadata $propertyMetadata Property metadata.
     * @param string $group Group name.
     *
     * @return boolean True if the property is defined for the group, false otherwise.
     */
    private function isPropertyInGroup(PropertyMetadata $propertyMetadata, $group)
    {
        $groups = $propertyMetadata->groups;

        return !$groups || !$group || in_array($group, $groups);
    }

    /**
     * Check if property is available for a specific version.
     *
     * @param PropertyMetadata $propertyMetadata Property metadata.
     * @param string $version Version number.
     *
     * @return boolean True if the property is available for the version, false otherwise.
     */
    private function isPropertyInVersion(PropertyMetadata $propertyMetadata, $version)
    {
        $context           = SerializationContext::create()->setVersion($version);
        $exclusionStrategy = new VersionExclusionStrategy($version);

        return !$exclusionStrategy->shouldSkipProperty($propertyMetadata, $context);
    }
}
