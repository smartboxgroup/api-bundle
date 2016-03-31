<?php

namespace Smartbox\ApiBundle\Metadata;

use Doctrine\Common\Annotations\Reader;
use Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;
use Metadata\PropertyMetadata;
use Smartbox\ApiBundle\Annotation\JsonSchemaFile;

/**
 * Class JsonSchemaGroupLoader
 */
class JsonSchemaGroupsDriver implements DriverInterface
{
    /** @var Reader  */
    protected $reader;

    /** @var string */
    protected $jsonSchemaFilesFolder;

    /**
     * JsonSchemaGroupsDriver constructor.
     *
     * @param Reader $reader
     * @param string $jsonSchemaFilesFolder
     */
    public function __construct(Reader $reader, $jsonSchemaFilesFolder)
    {
        $this->reader = $reader;
        $this->jsonSchemaFilesFolder = $jsonSchemaFilesFolder;
        if (!is_dir($this->jsonSchemaFilesFolder)) {
            throw new \InvalidArgumentException(sprintf(
                'The path "%s" does not exist or it\'s not a folder',
                $this->jsonSchemaFilesFolder
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $annotations = $this->reader->getClassAnnotations($class);
        $classMetadata = new ClassMetadata($name = $class->name);
        $groupsByProperty = [];

        foreach($annotations as $annotation) {
            if (!$annotation instanceof JsonSchemaFile) {
                continue;
            }

            $path = realpath($this->jsonSchemaFilesFolder . '/' . $annotation->file);
            if (false === $path) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'File "%s" found in "%s" for the annotation "%s" is missing in path "%s"',
                        $annotation->file,
                        $class->getName(),
                        self::class,
                        $this->jsonSchemaFilesFolder
                    )
                );
            }

            if (!is_readable($path)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'File "%s" found in "%s" for the annotation "%s" is not readable',
                        $path,
                        $class->getName(),
                        self::class
                    )
                );
            }

            $schema = json_decode(file_get_contents($path), true);
            if (null === $schema) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'File "%s" found in "%s" for the annotation "%s" does not contain a valid json schema',
                        $path,
                        $class->getName(),
                        self::class
                    )
                );
            }

            $classMetadata->fileResources[] = $path;

            if (isset($schema['views'])) {
                foreach($schema['views'] as $group => $fields) {
                    $fields = (array) $fields;
                    foreach ($fields as $field) {
                        if (!isset($groupsByProperty[$field])) {
                            $groupsByProperty[$field] = [];
                        }

                        $groupsByProperty[$field][] = $group;
                    }
                }
            }
        }

        foreach ($groupsByProperty as $propertyName => $groups) {
            $propertyMetadata = new PropertyMetadata($class->getName(), $propertyName);
            $propertyMetadata->groups = array_unique($groups);
            $classMetadata->addPropertyMetadata($propertyMetadata);
        }

        return $classMetadata;
    }
}
