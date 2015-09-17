<?php

namespace Smartbox\ApiBundle\Services\Doc;

use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Exclusion\VersionExclusionStrategy;
use JMS\Serializer\SerializationContext;
use Metadata\MetadataFactoryInterface;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use Nelmio\ApiDocBundle\Parser\PostParserInterface;
use Smartbox\ApiBundle\Services\Serializer\Exclusion\PreserveArrayTypeStrategy;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\PropertyMetadata;

class ValidationParser extends \Nelmio\ApiDocBundle\Parser\ValidationParser implements ParserInterface, PostParserInterface
{
    /**
     * @var \Metadata\MetadataFactoryInterface
     */
    protected $jmsFactory;

    /***
     * @param \Symfony\Component\Validator\MetadataFactoryInterface $factory
     * @param MetadataFactoryInterface $jmsFactory
     */
    public function __construct(
        \Symfony\Component\Validator\MetadataFactoryInterface $factory,
        MetadataFactoryInterface $jmsFactory
    ) {
        $this->factory = $factory;
        $this->jmsFactory = $jmsFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $input)
    {
        $className = $input['class'];
        $groups = $input['groups'];
        $version = $input['version'];

        return $this->doParse($className, array(), $version, $groups);
    }

    /**
     * Recursively parse constraints.
     *
     * @param  $className
     * @param  array $visited
     * @return array
     */
    protected function doParse($className, array $visited, $version = null, $groups = null)
    {
        $params = array();

        // Validator properties
        /** @var ClassMetadata $meta */
        $meta = $this->factory->getMetadataFor($className);
        $properties = $meta->getConstrainedProperties();

        $exclusionStrategies = array();
        $c = SerializationContext::create();
        $exclusionStrategies[] = new VersionExclusionStrategy($version);
        $exclusionStrategies[] = new PreserveArrayTypeStrategy();


        if (!empty($groups)) {
            $exclusionStrategies[] = new GroupsExclusionStrategy($groups);
        }

        $jmsMeta = $this->jmsFactory->getMetadataForClass($className);
        if (null === $jmsMeta) {
            throw new \InvalidArgumentException(sprintf("No metadata found for class %s", $className));
        }

        foreach ($properties as $index => $property) {
            /** @var ExclusionStrategyInterface $strategy */
            foreach ($exclusionStrategies as $strategy) {
                $metaProp = @$jmsMeta->propertyMetadata[$property];
                if ($metaProp && $strategy->shouldSkipProperty($metaProp, $c)) {
                    unset($properties[$index]);
                    break;
                }
            }
        }

        $refl = $meta->getReflectionClass();
        $defaults = $refl->getDefaultProperties();

        foreach ($properties as $property) {
            $validationParams = array();

            $validationParams['default'] = isset($defaults[$property]) ? $defaults[$property] : null;

            $pds = $meta->getPropertyMetadata($property);
            /** @var PropertyMetadata $propertyMetadata */
            foreach ($pds as $propertyMetadata) {
                $constraints = $propertyMetadata->getConstraints();

                foreach ($constraints as $constraint) {
                    $validationParams = $this->parseConstraint($constraint, $validationParams, $className, $visited);
                }
            }

            if (isset($validationParams['format'])) {
                $validationParams['format'] = join(', ', $validationParams['format']);
            }

            foreach (array('dataType', 'readonly', 'required', 'subType') as $reqprop) {
                if (!isset($validationParams[$reqprop])) {
                    $validationParams[$reqprop] = null;
                }
            }

            // check for nested classes with All constraint
            if (isset($validationParams['class']) && !in_array(
                    $validationParams['class'],
                    $visited
                ) && null !== $this->factory->getMetadataFor($validationParams['class'])
            ) {
                $visited[] = $validationParams['class'];
                $validationParams['children'] = $this->doParse($validationParams['class'], $visited);
            }

            $validationParams['actualType'] = isset($validationParams['actualType']) ? $validationParams['actualType'] : DataTypes::STRING;

            $params[$property] = $validationParams;
        }

        return $params;
    }

    /**
     * {@inheritDoc}
     */
    public function postParse(array $input, array $parameters)
    {
        foreach ($parameters as $param => $data) {
            if (isset($data['class']) && isset($data['children'])) {
                $paramInput = [
                    'class' => $data['class'],
                    'groups' => $input['groups'],
                    'version' => $input['version'],
                ];
                $parameters[$param]['children'] = array_merge(
                    $parameters[$param]['children'], $this->postParse($paramInput, $parameters[$param]['children'])
                );
                $parameters[$param]['children'] = array_merge(
                    $parameters[$param]['children'], $this->parse($paramInput, $parameters[$param]['children'])
                );
            }
        }

        return $parameters;
    }
}
