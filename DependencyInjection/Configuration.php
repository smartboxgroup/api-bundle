<?php

namespace Smartbox\ApiBundle\DependencyInjection;

use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\CoreBundle\Type\EntityInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    const API_CONTROLLER = 'SmartboxApiBundle:API:handleCall';

    // TODO: Move this definitions to ApiConfigurator
    const DATETIME = 'datetime';
    const INTEGER = 'integer';
    const FLOAT = 'float';
    const BOOL = 'bool';
    const STRING = 'string';

    const MODE_FILTER = 'filter';
    const MODE_BODY = 'body';
    const MODE_REQUIREMENT = 'requirement';

    public static $INPUT_MODES = array(self::MODE_BODY, self::MODE_FILTER, self::MODE_REQUIREMENT);

    public static $BASIC_TYPES = array(self::INTEGER, self::FLOAT, self::STRING, self::BOOL, self::DATETIME);
    public static $KEYWORDS = array(
        'filters',
        '_controller',
        '_generated',
        'api',
        ApiConfigurator::SERVICE_ID,
        ApiConfigurator::VERSION,
        ApiConfigurator::SERVICE_NAME,
        ApiConfigurator::METHOD_NAME,
        ApiConfigurator::METHOD_CONFIG,
        ApiConfigurator::INPUT
    );

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('smartbox_api');

        $rootNode
            ->children()
            ->scalarNode('userProvider')
                ->isRequired()
                ->info("Id of user provider service which implements Symfony\\Component\\Security\\Core\\User\\UserProviderInterface\n
    f.e.: security.user.provider.concrete.in_memory
                ")
            ->end()
            ->scalarNode('default_controller')->defaultValue(self::API_CONTROLLER)->end()
            ->booleanNode('throttling')
                ->defaultValue(false)
                ->info("Enable/Disable throttling (dafault: false).\n
    If throttling is enabled:
        - register bundles in AppKernel.php:
            new Noxlogic\\RateLimitBundle\\NoxlogicRateLimitBundle(),
            new Snc\\RedisBundle\\SncRedisBundle(),
        - configure bundles:
            noxlogic_rate_limit:
                storage_engine:             redis
                redis_client:               default_client
                rate_response_message:      'You exceeded the rate limit'
                display_headers:            true

            snc_redis:
                clients:
                    default:
                        type: predis
                        alias: default
                        dsn: redis://localhost
               ")
            ->end()
            ->append($this->addErrorCodesNode())
            ->append($this->addSuccessCodesNode())
            ->append($this->addEmptyBodyResponseCodes())
            ->append($this->addServicesNode())
            ->end()
            ->end();

        return $treeBuilder;
    }

    public function addErrorCodesNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('errorCodes');

        $node->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('id')
            ->info("List of error codes, e.g.::\n
    400: Bad Request, the request could not be understood by the server due to malformed syntax
    401: Unauthorized, the request requires user authentication
    403: Forbidden, the server understood the request, but is refusing to fulfill it
    404: Not Found, the server has not found anything matching the Request-URI
    **The success codes can be extended and changed")
            ->prototype('scalar')->end()
            ->end();

        return $node;
    }

    public function addTagsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('tags');
        $node
            ->info("List of tags associated to the method ")
            ->prototype('array')
                ->children()
                    ->scalarNode('message')->isRequired()->end()
                    ->scalarNode('color')->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }

    public function addSuccessCodesNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('successCodes');
        $node->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('id')
            ->info("List of success codes, e.g.::\n
    200: Success, the information returned with the response is dependent on the method used in the request
    201: Created, the request has been fulfilled and resulted in a new resource being created
    202: Accepted, the request has been accepted for processing, but the processing has not been completed
    204: No content, the server has fulfilled the request but does not need to return an entity-body
    **The success codes can be extended and changed")
            ->prototype('scalar')->end()
            ->end();

        return $node;
    }

    public function addEmptyBodyResponseCodes()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('restEmptyBodyResponseCodes');
        $node->isRequired()
            ->info("List of response codes were the APIBundle should enforce an empty body, e.g.: [301,202]")
            ->defaultValue([])
            ->prototype('scalar')->end()
            ->end();

        return $node;
    }

    public function addServicesNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('services');
        $node->isRequired()
            ->requiresAtLeastOneElement()
            ->cannotBeEmpty()
            ->useAttributeAsKey('id')
            ->prototype('array')
            ->info("This is the place where the name and version of the API must be defined, e.g.::\n
    http://soapheaders")
            ->children()
            ->scalarNode('parent')->end()
            ->scalarNode('name')->isRequired()->end()
            ->scalarNode('version')->isRequired()->end()
            ->scalarNode('soapHeadersNamespace')
            ->info("The SOAP headers namespace need to be defined.")
            ->isRequired()->end()
            ->arrayNode('propagateHttpHeadersToSoap')
                ->useAttributeAsKey('id')
                ->info("List of http headers to propagate as SOAP envelope headers in SOAP calls, e.g.::\n
    X-RateLimit-Limit: rateLimitLimit\n
    The headers must be present in order to be propagated. In this example the header is present when activating throttling")
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('removed')
            ->prototype('scalar')->end()
            ->end()
            ->append($this->addMethodsNode())
            ->end()
            ->end()
            ->end();

        return $node;
    }

    public function addMethodsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('methods');
        $node->isRequired()
            ->requiresAtLeastOneElement()
            ->cannotBeEmpty()
            ->useAttributeAsKey('name')
            ->info("Endpoint definitions.
    Example:
    services:
        demo_v1:
            name: demo
            version: v1
            methods:

            ## BOXES
                createBox:
                    description: Creates a box with the given information and returns its id
                    successCode: 201
                    input:
                        box: { type: Smartbox\\ApiBundle\\Tests\\Fixtures\\Entity\\Box, group: update, mode: body }
                    rest:
                        route: /box
                        httpMethod: POST
                    throttling:
                        limit: 10
                        period: 60
               ")
            ->prototype('array')
            ->children()
            ->scalarNode('successCode')->info('Success code to be returned')->defaultValue(200)->end()
            ->scalarNode('description')->info('Description of the method, it will be used in the documentation')->isRequired()->end()
            ->scalarNode('controller')->info('Controller to handle the requests to this method')->defaultValue('default')->end()
            ->arrayNode('roles')
            ->useAttributeAsKey('role')
            ->defaultValue(array('ROLE_USER'))
            ->prototype('scalar')
            ->info('Roles allowed to use this method')
            ->end()
            ->end()
            ->arrayNode('defaults')
            ->useAttributeAsKey('key')
            ->defaultValue(array())
            ->prototype('scalar')
            ->info('Default values')
            ->end()
            ->end()
            ->append($this->addInputNode())
            ->append($this->addOutputNode())
            ->append($this->addRestNode())
            ->append($this->addThrottlingNode())
            ->append($this->addTagsNode())
            ->end()
            ->end()
            ->end();

        return $node;
    }

    public function addInputNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('input');
        $node->info("Section where the input parameters are specified.");
        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
            ->scalarNode('description')->info('The description of the parameter, it will be used in the documentation.')
            ->defaultValue("")->end()
            ->scalarNode('type')
                ->info('The type of the input, it accepts scalar types (integer, double, string), entities (e.g.: MyNamespace\\MyEntity) and arrays of them (integer[], MyNamespace\\MyEntity[])')
            ->isRequired()->end()
            ->scalarNode('group')
                ->info('The group of the entity to be used, acts as a view of the entity model, determines the set of attributes to be used.')
            ->defaultValue(EntityInterface::GROUP_PUBLIC)->end()
            ->scalarNode('mode')
                ->info('Defines if the parameter is a requirement, filter or the body.\nBody: There can be only one input as the body,\n and it must be an Entity or array of entities.\nRequirement: Requirements are scalar parameters which are required.\nFilter: Filters are scalar parameters which are optional.')            ->defaultValue(Configuration::MODE_REQUIREMENT)
            ->validate()
            ->ifNotInArray(self::$INPUT_MODES)
            ->thenInvalid('Invalid database driver "%s"')
            ->end()
            ->end()
            ->scalarNode('format')->info('Regex with the format for the parameter, e.g.: d+')->defaultValue("[a-zA-Z0-9]+")->end()
            ->end()
            ->validate()
            ->ifTrue(
                function ($input) {
                    return ($input['mode'] == Configuration::MODE_BODY && !ApiConfigurator::isEntityOrArrayOfEntities(
                            $input['type']
                        ));
                }
            )
            ->thenInvalid('The body type must be a class implementing EntityInterface or an array of those')
            ->end()
            ->validate()
            ->ifTrue(
                function ($input) {
                    $isBasic = in_array($input['type'], Configuration::$BASIC_TYPES);

                    return ($input['mode'] != Configuration::MODE_BODY && !$isBasic);
                }
            )
            ->thenInvalid(
                'Except the body, all other inputs must be of basic types: '.join(', ', Configuration::$BASIC_TYPES)
            )
            ->end()
            ->end()
            ->validate()
            ->ifTrue(
                function ($input) {
                    $bodyCount = 0;
                    foreach ($input as $name => $conf) {
                        if ($conf['mode'] == Configuration::MODE_BODY) {
                            $bodyCount++;
                        }
                    }

                    return $bodyCount > 1;
                }
            )
            ->thenInvalid('There can be only 1 input declared as body for a method but more were found.')
            ->ifTrue(
                function ($input) {
                    foreach ($input as $name => $conf) {
                        if (in_array($name, Configuration::$KEYWORDS)) {
                            return true;
                        }
                    }

                    return false;
                }
            )
            ->thenInvalid('Invalid name. The names: ('.join(', ', self::$KEYWORDS).') are reserved for internal use')
            ->end();

        return $node;
    }

    public function addOutputNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('output');

        $node->info("Section where the output parameters are specified.");
        $node->children()
            ->scalarNode('type')->info('The type of the output, it accepts only entities (e.g.: MyNamespace\\MyEntity) and arrays of them (MyNamespace\\MyEntity[])')->isRequired()->end()
            ->scalarNode('group')
                ->info('The group of the entity to be used, acts as a view of the entity model, determines the set of attributes to be used.')
                ->defaultValue(EntityInterface::GROUP_PUBLIC)->end()
            ->end()
            ->validate()
            ->ifTrue(
                function ($output) {
                    return (!ApiConfigurator::isEntityOrArrayOfEntities($output['type']));
                }
            )
            ->thenInvalid('The output type must be a class implementing EntityInterface or an array of those')
            ->end()
            ->end();

        return $node;
    }

    public function addRestNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('rest');

        $node->isRequired()
            ->children()
            ->scalarNode('route')->info('Route for the this API method')->isRequired()->end()
            ->scalarNode('httpMethod')->info('HTTP verb for this API method')->isRequired()->end()
            ->end()
            ->end();

        return $node;
    }

    public function addThrottlingNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('throttling');

        $node
            ->info("Throttling works only when smartbox_api.throttling is set to true.\n
    Response headers:
        X-RateLimit-Limit:          Limit of requests in a time window
        X-RateLimit-Remaining:      Remaining requests in a time window
        X-RateLimit-Reset:          The remaining window before the rate limit resets in UTC epoch seconds
    Response status code when limit is exceeded: 429 Too Many Requests
            ")
            ->children()
                ->integerNode('limit')
                    ->info('Set limit of requests in specified period.')
                    ->isRequired()
                ->end()
                ->integerNode('period')
                    ->info('Set period to limit requests.')
                    ->isRequired()
                ->end()
            ->end();

        return $node;
    }
}
