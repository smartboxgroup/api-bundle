<?php

namespace Smartbox\ApiBundle\Metadata;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class JsonSchemaViewsRegistryBuilder
 */
class JsonSchemaViewsRegistryBuilder
{
    /**
     * @var Finder
     */
    protected $finder;

    /**
     * JsonSchemaViewsRegistryBuilder constructor.
     *
     * @param string $path
     * @param string $filter
     * @param bool   $deep
     */
    public function __construct($path, $filter = '*.view.json', $deep = true)
    {
        $this->finder = new Finder();
        $this->finder
            ->in($path)
            ->name($filter)
        ;

        if (!$deep) {
            $this->finder->depth(0);
        }
    }

    /**
     * Builds the definition for a JsonSchemaViewsRegistry
     *
     * @param string $class
     * @param array $arguments
     *
     * @return Definition
     */
    public function buildDefinition($class = JsonSchemaViewsRegistry::class, $arguments = [])
    {
        $definition = new Definition($class, $arguments);

        /** @var SplFileInfo $file */
        foreach ($this->finder as $file) {
            $schema = json_decode($file->getContents(), true);
            if (!$schema) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'File "%s" is not a valid JSON file',
                        $file->getPath()
                    )
                );
            }

            if (!isset($schema['name'])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid view definition in "%s": missing mandatory view "name" field',
                        $file->getPath()
                    )
                );
            }

            if (isset($schema['projections'])) {
                foreach ($schema['projections'] as $projection) {
                    if (!isset($projection['entity']) || !isset($projection['fields'])) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Invalid view definition in "%s": all projections must specify "entity" and "fields"',
                                $file->getPath()
                            )
                        );
                    }

                    $definition->addMethodCall('addGroup', [
                        $schema['name'], $projection['entity'], $projection['fields']
                    ]);
                }
            }
        }

        return $definition;
    }
}
