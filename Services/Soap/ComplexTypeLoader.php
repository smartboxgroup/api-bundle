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

class ComplexTypeLoader extends Definition\Loader\AnnotationClassLoader
{
    protected $aliasClass = 'BeSimple\SoapBundle\ServiceDefinition\Annotation\Alias';
    protected $complexTypeClass = 'BeSimple\SoapBundle\ServiceDefinition\Annotation\ComplexType';
    protected $jmsTypeClass = 'JMS\Serializer\Annotation\Type';
    protected $symfonyValidationNotBlank = 'Symfony\Component\Validator\Constraints\NotBlank';
    protected $symfonyValidationNotNull = 'Symfony\Component\Validator\Constraints\NotNull';

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
        $className = $data['phpType'];
        $group = $data['group'];
        $version = $data['version'];

        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $className));
        }

        $annotations = array();

        $class = new \ReflectionClass($className);
        if ($alias = $this->reader->getClassAnnotation($class, $this->aliasClass)) {
            $annotations['alias'] = $alias->getValue();
        }

        $jmsClassMeta = $this->getSerializer()->getMetadataFactory()->getMetadataForClass($className);
        $annotations['properties'] = new Collection('getName', 'BeSimple\SoapBundle\ServiceDefinition\ComplexType');

        foreach ($class->getProperties() as $property) {
            // Fetch NotBlank / NotNull from validation
            $notBlank = $this->reader->getPropertyAnnotation($property, $this->symfonyValidationNotBlank);
            if ($notBlank && $notBlank->groups) {
                $notBlank = in_array($group, $notBlank->groups);
            }
            $notNull = $this->reader->getPropertyAnnotation($property, $this->symfonyValidationNotNull);
            if ($notNull && $notNull->groups) {
                $notNull = in_array($group, $notNull->groups);
            }

            $isNullable = !$notBlank && !$notNull;
            $soapType = null;
            $inGroup = true;
            $inVersion = true;

            // JMS Metadata
            if (array_key_exists($property->name, $jmsClassMeta->propertyMetadata)) {
                /** @var PropertyMetadata $jmsPropertyMeta */
                $jmsPropertyMeta = $jmsClassMeta->propertyMetadata[$property->name];

                // Fetch JMS type
                $jmsType = $jmsPropertyMeta->type;

                $soapType = ApiConfigurator::getSoapTypeFor($jmsType['name']);

                // Fetch JMS groups
                $groups = $jmsPropertyMeta->groups;
                $inGroup = !$groups || !$group || in_array($group, $groups);

                // Serialization context
                $context = SerializationContext::create()->setVersion($version);
                $exclusionStrategy = new VersionExclusionStrategy($version);
                $inVersion = !$exclusionStrategy->shouldSkipProperty($jmsPropertyMeta, $context);
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
        return is_array($resource)
        && array_key_exists('phpType', $resource)
        && array_key_exists('group', $resource)
        && array_key_exists('version', $resource)
        && 'annotation_complextype' === $type;
    }

}