<?php

namespace Smartbox\ApiBundle\Services\Doc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\Routing\Route;

class ApiDocExtractor extends \Nelmio\ApiDocBundle\Extractor\ApiDocExtractor
{
    /**
     * Returns an array of data where each data is an array with the following keys:
     *  - annotation
     *  - resource.
     *
     * @param array $routes array of Route-objects for which the annotations should be extracted
     *
     * @return array
     */
    public function extractAnnotations(array $routes, $view = ApiDoc::DEFAULT_VIEW)
    {
        /** @var ApiConfigurator $configurator */
        $configurator = $this->container->get('smartapi.configurator');

        $array = [];
        $resources = [];
        $excludeSections = $this->container->getParameter('nelmio_api_doc.exclude_sections');

        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new \InvalidArgumentException(sprintf('All elements of $routes must be instances of Route. "%s" given', gettype($route)));
            }

            if ($method = $this->getReflectionMethod($route->getDefault('_controller'))) {
                if ('smartapi' == $route->getDefault('_generated')) {
                    $methodName = $route->getDefault(ApiConfigurator::METHOD_NAME);
                    $serviceId = $route->getDefault(ApiConfigurator::SERVICE_ID);
                    $methodConfig = $configurator->getConfig($serviceId, $methodName);
                    $serviceName = $route->getDefault(ApiConfigurator::SERVICE_NAME);
                    $version = $route->getDefault(ApiConfigurator::VERSION);

                    $successCode = $methodConfig['successCode'];
                    $statusCodes = $configurator->getErrorCodes();
                    $statusCodes[$successCode] = $configurator->getSuccessCodeDescription($successCode);

                    $annotationData = [
                        'section' => $serviceName.'/'.$version,
                        'views' => ['default', $serviceId],
                        'resource' => true,
                        'description' => $methodConfig['description'],
                        'statusCodes' => $statusCodes,
                        'authentication' => true,
                        'authenticationRoles' => $methodConfig['roles'],
                    ];

                    $annotationData['filters'] = [];

                    // If there is input
                    foreach ($methodConfig[ApiConfigurator::INPUT] as $paramName => $paramConfig) {
                        $jmsType = ApiConfigurator::getJMSType($paramConfig['type']);

                        if (false !== strpos($paramConfig['type'], ApiConfigurator::$arraySymbol)) {
                            $jmsType .= ' as '.$paramName;
                        }

                        switch ($paramConfig['mode']) {
                            case Configuration::MODE_BODY:
                                $annotationData['input'] = [
                                    'description' => $paramConfig['description'],
                                    'class' => $jmsType,
                                    'version' => $version,
                                    'parsers' => [
                                        'Smartbox\ApiBundle\Services\Doc\JmsMetadataParser',
                                        'Nelmio\ApiDocBundle\Parser\CollectionParser',
                                        'Smartbox\ApiBundle\Services\Doc\ValidationParser',
                                    ],
                                ];

                                if (array_key_exists('group', $paramConfig)) {
                                    $annotationData['input']['groups'] = [$paramConfig['group']];
                                }
                                break;
                            case Configuration::MODE_REQUIREMENT:
                                $annotationData['requirements'][$paramName] = [
                                    'name' => $paramName,
                                    'dataType' => $jmsType,
                                    'required' => true,
                                    'description' => $paramConfig['description'],
                                    'requirement' => $paramConfig['format'],
                                ];
                                break;
                            case Configuration::MODE_FILTER:
                                $annotationData['filters'][] = [
                                    'name' => $paramName,
                                    'dataType' => $jmsType,
                                    'required' => false,
                                    'description' => $paramConfig['description'],
                                    'requirement' => $paramConfig['format'],
                                ];
                                break;
                        }
                    }

                    // If there is an output for rest
                    if (array_key_exists('output', $methodConfig)) {
                        if (!in_array($methodConfig['successCode'], $configurator->getRestEmptyBodyResponseCodes())) {
                            $outputType = ApiConfigurator::getJMSType($methodConfig['output']['type']);
                            $annotationData['output'] = [
                                'class' => $outputType,
                                'version' => $version,
                                'parsers' => [
                                    'Smartbox\ApiBundle\Services\Doc\JmsMetadataParser',
                                    'Nelmio\ApiDocBundle\Parser\CollectionParser',
                                    'Smartbox\ApiBundle\Services\Doc\ValidationParser',
                                ],
                            ];

                            if (array_key_exists('group', $methodConfig['output'])) {
                                $annotationData['output']['groups'] = [$methodConfig['output']['group']];
                            }
                        }
                    }

                    $annotation = new ApiDoc($annotationData);
                    $annotation->setRoute($route);
                } else {
                    $annotation = $this->reader->getMethodAnnotation($method, self::ANNOTATION_CLASS);
                }

                if (
                    $annotation && !in_array($annotation->getSection(), $excludeSections) &&
                    (in_array($view, $annotation->getViews()) || (0 === count(
                                $annotation->getViews()
                            ) && ApiDoc::DEFAULT_VIEW === $view))
                ) {
                    if ($annotation->isResource()) {
                        if ($resource = $annotation->getResource()) {
                            $resources[] = $resource;
                        } else {
                            // remove format from routes used for resource grouping
                            $resources[] = str_replace('.{_format}', '', $route->getPath());
                        }
                    }

                    $annotationExtracted = $this->extractData($annotation, $route, $method);

                    if ('smartapi' == $route->getDefault('_generated')) {
                        $methodName = $route->getDefault(ApiConfigurator::METHOD_NAME);
                        $serviceId = $route->getDefault(ApiConfigurator::SERVICE_ID);
                        $methodConfig = $configurator->getConfig($serviceId, $methodName);

                        // Add info about requirements
                        $requirements = [];
                        foreach ($annotationExtracted->getRequirements() as $reqName => $reqParams) {
                            if (array_key_exists($reqName, $methodConfig[ApiConfigurator::INPUT])) {
                                $reqParams['dataType'] = ApiConfigurator::getJMSType(
                                    $methodConfig[ApiConfigurator::INPUT][$reqName]['type']
                                );
                                $reqParams['description'] = $methodConfig[ApiConfigurator::INPUT][$reqName]['description'];
                                $reqParams['requirement'] = $methodConfig[ApiConfigurator::INPUT][$reqName]['format'];

                                $requirements[$reqName] = $reqParams;
                            }
                        }
                        $annotationExtracted->setRequirements($requirements);

                        // Test if the fixture exists in the config, if not set it null
                        $fixturePath = isset($methodConfig['fixture']) ? $methodConfig['fixture'] : null;

                        if (!empty($fixturePath)) {
                            $methodConfig['fixture'] = $this->loadFixture($fixturePath, $configurator->getFixturePath());
                        }
                        $parameters = $annotationExtracted->getParameters();

                        foreach ($parameters as $parameter => $parameterConfig) {
                            $parameters[$parameter]['readonly'] = false;
                            $parameters[$parameter] = $this->cleanDatatype($parameters[$parameter]);
                        }
                        $annotationExtracted->setParameters($parameters);
                        $annotationExtracted->setDocumentation($this->getDocumentationFor($serviceId, $methodName, $methodConfig));
                    }

                    $array[] = ['annotation' => $annotationExtracted];
                }
            }
        }

        foreach ($this->annotationsProviders as $annotationProvider) {
            foreach ($annotationProvider->getAnnotations() as $annotation) {
                $route = $annotation->getRoute();
                $array[] = [
                    'annotation' => $this->extractData(
                        $annotation,
                        $route,
                        $this->getReflectionMethod($route->getDefault('_controller'))
                    ),
                ];
            }
        }

        rsort($resources);
        foreach ($array as $index => $element) {
            $hasResource = false;
            $pattern = $element['annotation']->getRoute()->getPath();

            foreach ($resources as $resource) {
                if (0 === strpos($pattern, $resource) || $resource === $element['annotation']->getResource()) {
                    $array[$index]['resource'] = $resource;

                    $hasResource = true;
                    break;
                }
            }

            if (false === $hasResource) {
                $array[$index]['resource'] = 'others';
            }
        }

        $methodOrder = ['GET', 'POST', 'PUT', 'DELETE'];
        usort(
            $array,
            function ($a, $b) use ($methodOrder) {
                if ($a['resource'] === $b['resource']) {
                    if ($a['annotation']->getRoute()->getPath() === $b['annotation']->getRoute()->getPath()) {
                        $methodA = array_search($a['annotation']->getRoute()->getRequirement('_method'), $methodOrder);
                        $methodB = array_search($b['annotation']->getRoute()->getRequirement('_method'), $methodOrder);

                        if ($methodA === $methodB) {
                            return strcmp(
                                $a['annotation']->getRoute()->getRequirement('_method'),
                                $b['annotation']->getRoute()->getRequirement('_method')
                            );
                        }

                        return $methodA > $methodB ? 1 : -1;
                    }

                    return strcmp(
                        $a['annotation']->getRoute()->getPath(),
                        $b['annotation']->getRoute()->getPath()
                    );
                }

                return strcmp($a['resource'], $b['resource']);
            }
        );

        return $array;
    }

    /**
     * Load fixture from fixture name.
     *
     * @param string $fixtureName
     * @param string $path
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function loadFixture($fixtureName, $path)
    {
        if ('@' != $fixtureName[0]) {
            throw new \Exception(sprintf('Fixture name should start with "@", "%s". given', $fixtureName));
        }

        if (empty($path)) {
            throw new \Exception('Fixtures path should not be empty');
        }

        $fixtureName = substr($fixtureName, 1);
        $path = $path.'/'.$fixtureName.'.json';

        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception(sprintf('Fixture "%s" not found, looking in "%s". The file doesn\'t exist or it\'s not readable', $fixtureName, $path));
        }

        $json = trim(file_get_contents($path));
        $data = $this->deHydrate(json_decode($json, true));

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Return dehydrated array.
     *
     * @param $data
     *
     * @return array
     */
    protected function deHydrate($data)
    {
        $hydrationKeys = ['_group', '_type', '_apiVersion'];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->deHydrate($value);
            } elseif (in_array($key, $hydrationKeys, true)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function cleanDatatype(array $parameter)
    {
        $typeField = 'collection' === $parameter['actualType'] ? 'subType' : 'dataType';
        $parts = explode('\\', $parameter[$typeField]);
        $parameter[$typeField] = end($parts);

        if (isset($parameter['children'])) {
            foreach ($parameter['children'] as $name => $child) {
                $parameter['children'][$name] = $this->cleanDatatype($child);
            }
        }

        return $parameter;
    }

    public function getDocumentationFor($serviceId, $methodName, $methodConfig)
    {
        $wsdlUrl = $this->container->get('router')->generate('_webservice_definition', ['webservice' => $serviceId]);

        return $this->container->get('templating')->render(
            'SmartboxApiBundle:doc:documentation.html.twig',
            [
                ApiConfigurator::METHOD_NAME => $methodName,
                ApiConfigurator::METHOD_CONFIG => $methodConfig,
                'wsdlUrl' => $wsdlUrl,
            ]
        );
    }
}
