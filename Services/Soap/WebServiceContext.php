<?php
namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapCommon\Converter\TypeConverterCollection;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\Config\Loader\LoaderInterface;

class WebServiceContext extends \BeSimple\SoapBundle\WebServiceContext
{

    /** @var  SoapServiceBinder */
    protected $serviceBinder;

    /** @var  ApiConfigurator */
    protected $apiConfigurator;

    protected $options;

    public function __construct(LoaderInterface $loader, TypeConverterCollection $converters, array $options)
    {
        $this->options = $options;
        parent::__construct($loader, $converters, $options);
    }

    /**
     * @return mixed
     */
    public function getApiConfigurator()
    {
        return $this->apiConfigurator;
    }

    /**
     * @param mixed $apiConfigurator
     */
    public function setApiConfigurator($apiConfigurator)
    {
        $this->apiConfigurator = $apiConfigurator;
    }

    public function getServiceBinder()
    {
        if (null === $this->serviceBinder) {
            $this->serviceBinder = new SoapServiceBinder(
                $this->apiConfigurator,
                $this->getServiceDefinition(),
                new $this->options['binder_request_header_class'](),
                new $this->options['binder_request_class'](),
                new $this->options['binder_response_class']()
            );
        }

        return $this->serviceBinder;
    }

    /**
     * Gets the WSDL file from the extended Dumper class
     * @param  mixed $endpoint
     * @return string
     */
    public function getWsdlFile($endpoint = null)
    {
        $file      = sprintf ('%s/%s.%s.wsdl', $this->options['cache_dir'], $this->options['name'], md5($endpoint));
        $cache = new ConfigCache($file, $this->options['debug']);

        if(!$cache->isFresh()) {
            $definition = $this->getServiceDefinition();

            if ($endpoint) {
                $definition->setOption('location', $endpoint);
            }

            $dumper = new Dumper($definition, array('stylesheet' => $this->options['wsdl_stylesheet']));
            $cache->write($dumper->dump());
        }

        return (string) $cache;
    }

}