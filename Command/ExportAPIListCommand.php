<?php

namespace Smartbox\ApiBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class GenerateFixturesCommand.
 */
class ExportAPIListCommand extends ContainerAwareCommand
{
    protected $apiConfig;
    protected $exportDir;
    protected $async;
    protected $httpMethod;
    protected $filePrefix;
    protected $role;

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            ->setName('smartbox:api:export-list')
            ->setDescription('Exports api and flows list into csv files. By default will be exported to /tmp')
            ->setHelp('Ex.: php app/console smartbox:api:export-list --export-dir "/tmp"
                 php app/console smartbox:api:export-list --async false --http-method GET 
            ')
            ->addOption('export-dir', 'd', InputOption::VALUE_OPTIONAL, 'The folder where to export the files. Ex.: -d /tmp/api', '/tmp')
            ->addOption('file-prefix', 'f', InputOption::VALUE_OPTIONAL, 'The prefix that will be added at the beginning of the file name. By default will be related to the date / time. Ex.: -f check-list', '')
            ->addOption('http-method', 'm', InputOption::VALUE_OPTIONAL, 'If set, will only export the corresponding flows for this HTTP METHOD. Ex.: -m POST', '')
            ->addOption('async', 'a', InputOption::VALUE_OPTIONAL, 'If set, will only export asynchronous flows if true, or synchronous flows if false. Ex.: -a true', '')
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'If set, will only export flows associated to that role. Ex.: -r ROLE_USER', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->exportDir = $input->getOption('export-dir');
        $this->async = ucfirst($input->getOption('async'));
        $this->httpMethod = strtoupper($input->getOption('http-method'));
        $this->filePrefix = $input->getOption('file-prefix');
        $this->role = $input->getOption('role');

        $configurator = $this->getContainer()->get('smartapi.configurator');
        $this->apiConfig = $configurator->getConfig();

        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir);
        }

        if (!$this->filePrefix) {
            $this->filePrefix = date('Ymd_His');
        }
        $apiFile = $this->exportDir.'/'.$this->filePrefix.'_api_list.csv';
        $flowFile = $this->exportDir.'/'.$this->filePrefix.'_flow_list.csv';
        $apiFilePointer = fopen($apiFile, 'w');
        $flowFilePointer = fopen($flowFile, 'w');

        $flowsList = [];
        $nbApi = 0;
        foreach ($this->apiConfig as $keyConfig) {
            foreach ($keyConfig['methods'] as $methodName => $method) {
                $api = [];
                $api['API'] = $methodName;
                $api['Space'] = $keyConfig['name'].'_'.$keyConfig['version'];
                $api['Description'] = $method['description'];
                $api['Success Code'] = $method['successCode'];
                $api['REST HTTP Method'] = $method['rest']['httpMethod'];
                $api['Asynchronous'] = (true == $method['defaults']['async']) ? 'True' : 'False';
                $api['Throttling'] = $method['throttling']['limit'].' / '.$method['throttling']['period'];

                $api['Max Output Elements'] = 1;
                if (isset($method['output']['limitElements'])) {
                    $api['Max Output Elements'] = $method['output']['limitElements'];
                }

                $api['Max Input Elements'] = 1;
                if (is_array($method['input'])) {
                    $limitExists = 0;
                    $sum = 0;
                    foreach ($method['input'] as $object) {
                        if (isset($object['limitElements'])) {
                            $limitExists = 1;
                            $sum += $object['limitElements'];
                        }
                    }
                    if ($limitExists) {
                        $api['Max Input Elements'] = $sum;
                    }
                }

                $export = true;
                if ($this->async && $api['Asynchronous'] != $this->async) {
                    $export = false;
                }
                if ($this->httpMethod && $api['REST HTTP Method'] && $api['REST HTTP Method'] != $this->httpMethod) {
                    $export = false;
                }
                if ($this->role && is_array($method['roles']) && count($method['roles']) > 0 && !in_array($this->role, $method['roles'])) {
                    $export = false;
                }

                if (false == $export) {
                    $api = array_pop($api); // We remove the last API that should not be exported
                } else {
                    // Flows
                    if (is_array($method['tags'])) {
                        $flows = [];
                        foreach ($method['tags'] as $tag) {
                            if ('00cc00' == $tag['color']) { // Flows are only identified amongst tags with their color.
                                $flows[] = $tag['message'];
                                $flowsList[$tag['message']] = [];
                                $flowsList[$tag['message']]['Flow'] = $tag['message'];
                                $flowsList[$tag['message']] = array_merge($flowsList[$tag['message']], $api);
                            }
                        }
                        $api['Flows'] = implode(' ', $flows);
                    }

                    $api['Roles'] = implode(' ', $method['roles']);

                    // API Export
                    if (0 == $nbApi) { // Headers
                        $titles = array_keys($api);
                        fputcsv($apiFilePointer, $titles);
                    }
                    fputcsv($apiFilePointer, $api);
                    ++$nbApi;
                }
            }
        }
        $nbFlow = 0;
        foreach ($flowsList as $flow) {
            if (0 == $nbFlow) {
                $titles = array_keys($flow);
                fputcsv($flowFilePointer, $titles);
            }
            fputcsv($flowFilePointer, $flow);
            ++$nbFlow;
        }
        fclose($apiFilePointer);
        fclose($flowFilePointer);

        $output->writeln($nbApi.' APIs and '.$nbFlow.' Flows were exported to '.$apiFile.' and '.$flowFile);
    }
}
