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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Count;
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

            if (in_array($validationParams['class'], $visited)) {
                continue;
            }

            // check for nested classes with All constraint
            if (
                isset($validationParams['class']) &&
                null !== $this->factory->getMetadataFor($validationParams['class'])
            ) {
                $visited[] = $validationParams['class'];
                $validationParams['children'] = $this->doParse($validationParams['class'], $visited, $version, $groups);
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

    /**
     * {@inheritdoc}
     */
    protected function parseConstraint(Constraint $constraint, $vparams, $className, &$visited = array())
    {
        $validationParams = parent::parseConstraint($constraint, $vparams, $className, $visited);
        $validationParams = $this->parseCountConstraint($constraint, $validationParams);
        $validationParams = $this->parseDataType($validationParams);

        return $validationParams;
    }

    /**
     * Method to parse if constraint is count type to add the information in validation parameters.
     *
     * @param Constraint $constraint The constraint metadata object.
     * @param array $validationParams The existing validation parameters.
     *
     * @return array
     */
    private function parseCountConstraint(Constraint $constraint, array $validationParams)
    {
        if ($constraint instanceof Count) {
            $validationParams['actualType'] = DataTypes::COLLECTION;
            $messages = [];
            if (isset($constraint->min)) {
                $messages[] = "min: {$constraint->min}";
            }

            if (isset($constraint->max)) {
                $messages[] = "max: {$constraint->max}";
            }

            $validationParams['format'][] = '{count: ' . join(', ', $messages) . '}';
        }

        return $validationParams;
    }

    /**
     * Method to parse the dataType to see if it is a primitive type or it is a class.
     *
     * @param array $validationParams The existing validation parameters.
     *
     * @return array
     */
    private function parseDataType(array $validationParams)
    {
        $validationParams['class'] = null;

        if (
            isset($validationParams['dataType']) &&
            !DataTypes::isPrimitive(trim($validationParams['dataType'], '\\')) &&
            class_exists($validationParams['dataType'])
        ) {
            $validationParams['actualType'] = DataTypes::MODEL;
            $validationParams['class']      = $validationParams['dataType'];
        }

        return $validationParams;
    }
}
