<?php

namespace Smartbox\ApiBundle\Metadata;

use Doctrine\Common\Annotations\Reader;
use Metadata\Driver\DriverInterface;
use Smartbox\ApiBundle\Annotation\JsonSchemaFile;

/**
 * Class JsonSchemaGroupLoader
 */
class JsonSchemaGroupsDriver implements DriverInterface
{
    const ANNOTATION_JSON_SCHEMA_FILE = 'JsonSchemaFile';

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
        $annotation = $this->reader->getClassAnnotation($class, self::ANNOTATION_JSON_SCHEMA_FILE);
        if ($annotation instanceof JsonSchemaFile) {
            // TODO
            // load file and parse it
        }
    }
}
