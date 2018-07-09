<?php

namespace Smartbox\ApiBundle\Services\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * Allows Nelmio Api Doc Bundle to preserve the array subtype detected by JMS serializes to be overwritten by the
 * Symfony array validator which doesn't recognize subtypes.
 *
 * Class PreserveArrayTypeStrategy
 */
class PreserveArrayTypeStrategy implements ExclusionStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $context)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $context)
    {
        return
            isset($property->type['name']) &&
            'array' === $property->type['name']
        ;
    }
}
