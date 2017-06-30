<?php

namespace Smartbox\ApiBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class GenerateFixturesCommand
 */
class ExportAPIListCommand extends ContainerAwareCommand
{
    protected $apiConfig;
    protected $exportDir;

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('smartbox:api:export-list')
            ->setDescription('Exports api and flows list into csv files. By default will be exported to /tmp')
            ->setHelp("Ex.: app/console smartbox:api:export-list -f \"/tmp\"")
            ->addOption('export-dir', 'd', InputOption::VALUE_OPTIONAL, 'The folder where to export the files. Ex.: /tmp/api', "/tmp");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->exportDir = $input->getOption('export-dir');

        $configurator = $this->getContainer()->get('smartapi.configurator');
        $this->apiConfig = $configurator->getConfig();

        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir);
        }

        $apiFile = $this->exportDir . "/" . date('Ymd_His') . '_api_list.csv';
        $flowFile = $this->exportDir . "/" . date('Ymd_His') . '_flow_list.csv';
        $apiFilePointer = fopen($apiFile, 'w');
        $flowFilePointer = fopen($flowFile, 'w');

        $flowsList = [];
        $nbApi = 0;
        foreach ($this->apiConfig as $keyConfig) {
            foreach ($keyConfig['methods'] as $methodName => $method) {
                $api = [];
                $api['API'] = $methodName;
                $api['Space'] = $keyConfig['name'] . '_' . $keyConfig['version'];
                $api['Description'] = $method['description'];
                $api['Success Code'] = $method['successCode'];
                $api['REST HTTP Method'] = $method['rest']['httpMethod'];
                $api['Asynchronous'] = ($method['defaults']['async'] == true) ? 'True' : 'False';
                $api['Throttling'] = $method['throttling']['limit'] . ' / ' . $method['throttling']['period'];

                $api['Max Output Elements'] = 1;
                if (isset($method['output']['limitElements'])) {
                    $api['Max Output Elements'] = $method['output']['limitElements'];
                }

                $api['Max Input Elements'] = 1;
                if (is_array($method['input'])) {
                    $limitExists = 0;
                    $sum = 0;
                    foreach ($method['input'] as $object) {
                        if (isset($object['limitElements'])){
                            $limitExists = 1;
                            $sum += $object['limitElements'];
                        }
                    }
                    if ($limitExists) {
                        $api['Max Input Elements'] = $sum;
                    }
                }

                if (is_array($method['tags'])) {
                    $flows = [];
                    foreach ($method['tags'] as $tag) {
                        if ($tag['color'] == '00cc00') { // Flows are only identified amongst tags with their color.
                            $flows[] = $tag['message'];
                            $flowsList[$tag['message']] = [];
                            $flowsList[$tag['message']]['Flow'] = $tag['message'];
                            $flowsList[$tag['message']] = array_merge($flowsList[$tag['message']], $api);
                        }
                    }
                    $api['Flows'] = implode(" ", $flows);
                }
                if ($nbApi == 0) {
                    $titles = array_keys($api);
                    fputcsv($apiFilePointer, $titles);
                }
                fputcsv($apiFilePointer, $api);
                $nbApi++;
            }
        }
        $nbFlow = 0;
        foreach ($flowsList as $flow) {
            if ($nbFlow == 0) {
                $titles = array_keys($flow);
                fputcsv($flowFilePointer, $titles);
            }
            fputcsv($flowFilePointer, $flow);
            $nbFlow++;
        }
        fclose($apiFilePointer);
        fclose($flowFilePointer);

        $output->writeln($nbApi . " APIs and " . $nbFlow . " Flows were exported to " . $apiFile . " and " . $flowFile);
    }
}