<?php

namespace Smartbox\ApiBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Yaml\Yaml;

/**
 * Class GenerateFixturesCommand
 */
class GenerateSoapUIProjectCommand extends ContainerAwareCommand
{
//    const ENDPOINT = "ganesh-tt-one17.smartbox-test.local";
    const ENDPOINT = "real.ganesh.local";

    protected $methodOption;
    protected $testFileOption;
    protected $endpointOption;


        /** {@inheritdoc} */
    protected function configure()
    {
        // TODO: We should base the generation only on the YAML test file...
        $this
            ->setName('smartbox:api:generate-soapui')
            ->setDescription('Generate a sample SoapUI project for a flow, using the fixture of a YAML test file.')
            ->setHelp("Ex.: app/console smartbox:api:generate-soapui -m sendProductInformation -t \"Product/sendProductInformation\" -p ganesh-tt-one17.smartbox-test.local")
            ->addOption('method', 'm', InputOption::VALUE_OPTIONAL, 'The Soap method to work with', "sendProductInformation")
            ->addOption('test-file', 't', InputOption::VALUE_OPTIONAL, 'The Flow test file to use for fixture and headers. Ex.: Product/sendProductInformation', "Product/sendProductInformation")
            ->addOption('endpoint', 'p', InputOption::VALUE_REQUIRED, 'Endpoint of the API. Ex.: ganesh-tt-one17.smartbox-test.local', self::ENDPOINT)
            // ->addOption('force', 'f', InputOption::VALUE_REQUIRED, 'Force the generation of the SoapUI project, even if it already exists', "")
            //->addOption('all', 'a', InputOption::VALUE_NONE, 'Create SoapUI projects for all the existing methods', "")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->methodOption = $input->getOption('method');
        $this->testFileOption = $input->getOption('test-file');
        $this->endpointOption = $input->getOption('endpoint');

        $configurator = $this->getContainer()->get('smartapi.configurator');
        $config = $configurator->getConfig();

        foreach ($config as $keyConfig) {
            foreach ($keyConfig['methods'] as $methodName => $method) {
                if ($methodName == $this->methodOption) {
                    $space = $keyConfig['name'];
                    $version = $keyConfig['version'];
                    $soapUIProjectContent = $this->generateSoapUI($methodName, $method, $space, $version);
                    $soapUIProjectFile = "app/Resources/SoapUIProjects/".$space."_".$version." ".str_replace("/","_",$this->testFileOption).".SoapUIProject.xml";
//                    $soapUIProjectFile = "app/Resources/SoapUIProjects/".str_replace("/","_",$this->testFileOption)." ".$space."_".$version.".SoapUIProject.xml";
                    file_put_contents($soapUIProjectFile, $soapUIProjectContent);
                    break;
                }
            }
        }
    }

    protected function generateSoapUI($methodName, $method, $space, $version) {
        $api = [];
        $ymlFile = "app/config/flows/real/".$this->testFileOption.".yml";
        $steps = $this->parseSteps($ymlFile); // Information gathered from the Flow Test File
        if (!$steps) {
            die($this->testFileOption. " not found or empty");
        }
        foreach ($steps as $step) {
            if ($step['type'] == 'api' && $step['method'] == $methodName && $step['service'] == $space."_".$version) {
                $api['fixture'] = $step['in']; // @Product/productInformation
                $api['headers'] = $step['headers'];
            }
        }
        if (!$api['fixture']) {
            $api['fixture'] = $method['fixture']; // The fixture taken from the method, and not from the test file
        }
        $api['fixture'] = str_replace("@","",$api['fixture']); // We remove the leading @
        $fixtureFile = "app/Resources/Fixtures/".$api['fixture'].".json";
        $api['fixtureContent'] = "";
        if (file_exists($fixtureFile)) {
            $api['fixtureContent'] = file_get_contents($fixtureFile);
        }
        if (!$api['fixtureContent']) {
            echo "The fixture is empty !";
        }

        $api['endpoint'] = "http://".$this->endpointOption;
        $api['projectName'] = $space."_".$version." ".$methodName." ".$this->testFileOption;
        $api['methodName'] = $methodName;
        $api['path'] = "/api/rest/".$space."/".$version.$method['rest']['route'];
        $api['httpMethod'] = $method['rest']['httpMethod'];

        $content = $this->getContainer()->get('templating')->render('SmartboxApiBundle:Command:SoapUIProject.xml.twig',['api' => $api] ); // mainBundle:Email:default.html.twig
        return $content;
    }

    protected function parseSteps($ymlFile) {
        if (file_exists($ymlFile)) {
            $steps = Yaml::parse(file_get_contents($ymlFile))['steps'];
            return $steps;
        } else {
            return false;
        }
    }
}