<?php

namespace Smartbox\ApiBundle\Command;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

class PrintableDumpCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setDescription('Dump the api documentation as a printable html file')
            ->setName('smartbox:api:dumpPrintable')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = $this->getContainer()->get('nelmio_api_doc.formatter.html_formatter');

        $formatter->setMotdTemplate('SmartboxApiBundle:doc:motd.html.twig');
        
        $formatter->setEnableSandbox(false);
        
        $extractedDoc = $this->getContainer()->get('nelmio_api_doc.extractor.api_doc_extractor')->all("default");
        
        $formattedDoc = $formatter->format($extractedDoc);

        $output->writeln($formattedDoc);
    }
}
