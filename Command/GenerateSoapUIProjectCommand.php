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
    const SOAPUI_PROJECTS_DIR = "app/Resources/SoapUIProjects";

    protected $apiConfig;
    protected $methodOption;
    protected $flowTestFileOption;
    protected $endpointOption;
//    protected $allOption;


    /** {@inheritdoc} */
    protected function configure()
    {
        // TODO: We should base the generation only on the YAML test file...
        $this
            ->setName('smartbox:api:generate-soapui')
            ->setDescription('Generate a sample SoapUI project for a flow, using the api defined in a YAML test file. The generated XML file is saved in '. self::SOAPUI_PROJECTS_DIR)
            ->setHelp("Ex.: app/console smartbox:api:generate-soapui -f \"Product/sendProductInformation\" -p ganesh-tt-one17.smartbox-test.local")
            ->addOption('flow-test-file', 'f', InputOption::VALUE_OPTIONAL, 'The Flow test file to use for fixtures and headers. Ex.: Product/sendProductInformation')
            ->addOption('endpoint', 'p', InputOption::VALUE_REQUIRED, 'Endpoint of the API. Ex.: ganesh-tt-one17.smartbox-test.local', self::ENDPOINT)
            // ->addOption('force', 'f', InputOption::VALUE_REQUIRED, 'Force the generation of the SoapUI project, even if it already exists', "")
            //->addOption('all', 'a', InputOption::VALUE_NONE, 'Create SoapUI projects for all the existing methods.', "")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;


        $this->flowTestFileOption = $input->getOption('flow-test-file');
        $this->endpointOption = $input->getOption('endpoint');
//        $this->allOption = $input->getOption('all');

        $configurator = $this->getContainer()->get('smartapi.configurator');
        $this->apiConfig = $configurator->getConfig();

        $exportDir = self::SOAPUI_PROJECTS_DIR;
        if (!is_dir($exportDir)) {
            mkdir($exportDir);
        }

        $soapUIProjectFile = $exportDir."/".$this->endpointOption." ".str_replace("/","__",$this->flowTestFileOption).".SoapUIProject.xml";

        $apiConfig['projectName'] = $this->endpointOption." ". $this->flowTestFileOption;
        $apiConfig['endpoint'] = $this->endpointOption;

        // Find apis in yml flow test file
        $apis = $this->generateAPIs($this->flowTestFileOption);

        // Generate XML content
        $soapUIProjectContent = $this->getContainer()->get('templating')->render('SmartboxApiBundle:Command:SoapUIProject.xml.twig',['apiConfig' => $apiConfig, 'apis' => $apis] ); // mainBundle:Email:default.html.twig

        // Save the SoapUI project
        if ($soapUIProjectContent) {
            file_put_contents($soapUIProjectFile, $soapUIProjectContent);
            echo "\"$soapUIProjectFile\" was generated\n";
        } else {
            throw new \Exception("\"$soapUIProjectFile\" could not be generated.");
        }


        // Todo: EXPORT ALL APIs
//        foreach ($this->apiConfig as $keyConfig) {
//            foreach ($keyConfig['methods'] as $methodName => $method) {
//                $space = $keyConfig['name'];
//                $version = $keyConfig['version'];
//                $soapUIProjectFile = $exportDir."/".$space."_".$version." ".str_replace("/","_",$this->flowTestFileOption).".SoapUIProject.xml";
////                      $soapUIProjectFile = "app/Resources/SoapUIProjects/".str_replace("/","_",$this->testFileOption)." ".$space."_".$version.".SoapUIProject.xml";
//                $soapUIProjectContent = $this->generateSoapUI($methodName, $method, $space, $version);
//                if ($soapUIProjectContent) {
//                    file_put_contents($soapUIProjectFile, $soapUIProjectContent);
//                    echo "$soapUIProjectFile was generated\n";
//                } else {
//                    throw new \Exception("$soapUIProjectFile could not be generated.");
//                }
//                break;
//            }
//        }
    }

    protected function generateAPIs($flowTestFileOption) {
        $apis = [];
        $ymlFile = "app/config/flows/real/".$flowTestFileOption.".yml";
        $steps = $this->parseSteps($ymlFile); // Information gathered from the Flow Test File
        if (!$steps) {
            die($this->flowTestFileOption. " not found or empty");
        }
        $i = 0;
        foreach ($steps as $step) {
            if ($step['type'] == 'api') { // && $step['method'] == $methodName && $step['service'] == $space."_".$version) {
                $service = $step['service'];
                $version = $this->apiConfig[$service]['version']; // vo
                $space= $this->apiConfig[$service]['name']; // checks

                $methodName = $step['method'];

                if (!isset($this->apiConfig[$service]['methods'][$methodName])) {
                    throw new \Exception("Method $methodName was not found");
                } else {
                    $method = $this->apiConfig[$service]['methods'][$methodName];
                }

                $apis[$i]['methodName'] = $methodName;
                $apis[$i]['space'] = $space;
                $apis[$i]['version'] = $version;

                // Fixture
                $apis[$i]['fixtureContent'] = "";
                $fixture = "";
                if ($step['in']) {
                    $fixture = $step['in']; // @Product/productInformation
                } elseif ($method['fixture']) {
                    $fixture = $method['fixture']; // The fixture taken from the method, and not from the test file
                }

                if ($fixture) {
                    $fixture = str_replace("@","",$fixture); // We remove the leading @
                    $apis[$i]['fixture'] = $fixture; // @Product/productInformation
                    $fixtureFile = "app/Resources/Fixtures/".$apis[$i]['fixture'].".json";
                    $apis[$i]['fixtureContent'] = "";
                    if (file_exists($fixtureFile)) {
                        $apis[$i]['fixtureContent'] = file_get_contents($fixtureFile);
                    }
                }

                // Headers
                $apis[$i]['headers'] = $step['headers'];
                $apis[$i]['endpoint'] = "http://".$this->endpointOption;
                $apis[$i]['projectName'] = $space."_".$version." ".$this->flowTestFileOption;
                $apis[$i]['path'] = "/api/rest/".$space."/".$version.$method['rest']['route'];
                $apis[$i]['httpMethod'] = $method['rest']['httpMethod'];
                $i++;
            }
        }
        return $apis;
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