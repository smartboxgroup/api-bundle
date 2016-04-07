<?php

namespace Smartbox\ApiBundle\Services\Doc;


use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Exclusion\VersionExclusionStrategy;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\SerializationContext;
use Metadata\MetadataFactoryInterface;
use Nelmio\ApiDocBundle\Util\DocCommentExtractor;

/**
 * Class JmsMetadataParser
 *
 * This class extends  \Nelmio\ApiDocBundle\Parser\JmsMetadataParser with the only purpose of excluding from the parsing
 * those fields which don't belong to the API version being documented.
 *
 * It was necessary to copy here some code (like the attributes and the constructor) because it was declared private on
 * the parent class.
 *
 * @package Smartbox\ApiBundle\Services\Doc
 */
class JmsMetadataParser extends \Nelmio\ApiDocBundle\Parser\JmsMetadataParser
{
    /**
     * @var \Metadata\MetadataFactoryInterface
     */
    private $factory;

    /**
     * @var PropertyNamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var \Nelmio\ApiDocBundle\Util\DocCommentExtractor
     */
    private $commentExtractor;

    public function __construct(
        MetadataFactoryInterface $factory,
        PropertyNamingStrategyInterface $namingStrategy,
        DocCommentExtractor $commentExtractor
    ) {
        $this->factory = $factory;
        $this->namingStrategy = $namingStrategy;
        $this->commentExtractor = $commentExtractor;
        parent::__construct($factory, $namingStrategy, $commentExtractor);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $input)
    {
        $className = $input['class'];
        $groups = $input['groups'];
        $version = $input['version'];

        return $this->doParse($className, array(), $groups, $version);
    }

    /**
     * Recursively parse all metadata for a class
     *
     * @param  string $className Class to get all metadata for
     * @param  array $visited Classes we've already visited to prevent infinite recursion.
     * @param  array $groups Serialization groups to include.
     * @return array                     metadata for given class
     * @throws \InvalidArgumentException
     */
    protected function doParse($className, $visited = array(), array $groups = array(), $version = null)
    {
        $meta = $this->factory->getMetadataForClass($className);

        if (null === $meta) {
            throw new \InvalidArgumentException(sprintf("No metadata found for class %s", $className));
        }

        $exclusionStrategies = array();
        if ($groups) {
            $exclusionStrategies[] = new GroupsExclusionStrategy($groups);
        }

        if ($version) {
            $exclusionStrategies[] = new VersionExclusionStrategy($version);
        }

        $params = array();

        $reflection = new \ReflectionClass($className);
        $defaultProperties = array_map(
            function ($default) {
                if (is_array($default) && count($default) === 0) {
                    return null;
                }

                return $default;
            },
            $reflection->getDefaultProperties()
        );

        // iterate over property metadata
        foreach ($meta->propertyMetadata as $item) {
            if (!is_null($item->type)) {
                $name = $this->namingStrategy->translateName($item);

                $dataType = $this->processDataType($item);

                // apply exclusion strategies
                foreach ($exclusionStrategies as $strategy) {
                    if (true === $strategy->shouldSkipProperty($item, SerializationContext::create())) {
                        continue 2;
                    }
                }

                if (!$dataType['inline']) {
                    $params[$name] = array(
                        'dataType' => $dataType['normalized'],
                        'actualType' => $dataType['actualType'],
                        'subType' => $dataType['class'],
                        'required' => false,
                        'default' => isset($defaultProperties[$item->name]) ? $defaultProperties[$item->name] : null,
                        //TODO: can't think of a good way to specify this one, JMS doesn't have a setting for this
                        'description' => $this->getDescription($item),
                        'readonly' => $item->readOnly,
                        'sinceVersion' => $item->sinceVersion,
                        'untilVersion' => $item->untilVersion,
                    );

                    if (!is_null($dataType['class']) && false === $dataType['primitive']) {
                        $params[$name]['class'] = $dataType['class'];
                    }
                }

                // we can use type property also for custom handlers, then we don't have here real class name
                if (!class_exists($dataType['class'])) {
                    continue;
                }

                // if class already parsed, continue, to avoid infinite recursion
                if (in_array($dataType['class'], $visited)) {
                    continue;
                }

                // check for nested classes with JMS metadata
                if ($dataType['class'] && false === $dataType['primitive'] && null !== $this->factory->getMetadataForClass(
                        $dataType['class']
                    )
                ) {
                    $visitedForNext = array_merge([$dataType['class']],$visited);

                    $children = $this->doParse($dataType['class'], $visitedForNext, $groups);

                    if ($dataType['inline']) {
                        $params = array_merge($params, $children);
                    } else {
                        $params[$name]['children'] = $children;
                    }
                }
            }
        }

        return $params;
    }
}
