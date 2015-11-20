<?php
namespace Smartbox\ApiBundle\Services\Soap;


class Dumper extends \BeSimple\SoapWsdl\Dumper\Dumper
{
    /**
     * Overrides the add methods class in order to be WSI compliant
     */
    protected function addMethods()
    {
        $this->addComplexTypes();
        $this->addPortType();
        $this->addMessages($this->definition->getMessages());

        foreach ($this->definition->getMethods() as $method) {
            $this->addPortOperation($method);

            foreach ($method->getVersions() as $version) {
                $this->getVersion($version)->addOperation($method);
            }
        }
    }
}