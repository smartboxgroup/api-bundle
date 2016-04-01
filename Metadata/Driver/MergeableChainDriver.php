<?php

namespace Smartbox\ApiBundle\Metadata\Driver;

use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;

/**
 * Class MergeableChainDriver
 */
class MergeableChainDriver implements DriverInterface
{
    /**
     * @var DriverInterface[]
     */
    protected $drivers;

    public function __construct(array $drivers = [])
    {
        $this->drivers = $drivers;
    }

    public function addDriver(DriverInterface $driver)
    {
        $this->drivers[] = $driver;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        /** @var MergeableClassMetadata $metadata */
        $metadata = null;
        foreach ($this->drivers as $driver) {
            if (null !== $currentMetadata = $driver->loadMetadataForClass($class)) {
                if (!$currentMetadata instanceof MergeableClassMetadata) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Metadata of class "%s" is not an instance of "%s". "%s" supports only mergeable'.
                            ' metadata',
                            get_class($currentMetadata),
                            MergeableClassMetadata::class,
                            self::class
                        )
                    );
                }

                if (null === $metadata) {
                    $metadata = $currentMetadata;
                } else {
                    $metadata->merge($currentMetadata);
                }
            }
        }

        return $metadata;
    }
}
