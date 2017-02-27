<?php

namespace Smartbox\ApiRestClient;

use JMS\Serializer\Handler\ArrayCollectionHandler;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Handler\PhpCollectionHandler;
use JMS\Serializer\Handler\PropelCollectionHandler;
use JMS\Serializer\SerializerBuilder;

/**
 * Class JMSSerializerBuilder.
 */
class JMSSerializerBuilder
{
    /**
     * Build instance of JMS serializer wih correct date format.
     *
     * @return \JMS\Serializer\Serializer
     */
    public static function buildSerializer()
    {
        $jmsConfigurator = function (HandlerRegistry $handlerRegistry) {
            $handlerRegistry->registerSubscribingHandler(new DateHandler('Y-m-d\TH:i:s.uP'));
            $handlerRegistry->registerSubscribingHandler(new PhpCollectionHandler());
            $handlerRegistry->registerSubscribingHandler(new ArrayCollectionHandler());
            $handlerRegistry->registerSubscribingHandler(new PropelCollectionHandler());
        };

        $serializer = SerializerBuilder::create()
            ->configureHandlers($jmsConfigurator)
            ->build();

        return $serializer;
    }
}
