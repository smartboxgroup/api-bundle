<?php

namespace Smartbox\ApiBundle\Command;

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClientGeneratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Generate Bifrost Client')
            ->setName('smartbox:api:generateClient');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $className = "BifrostSDK";

        $namespace = "BifrostClient";

        $extractedDoc = $this->getContainer()->get('nelmio_api_doc.extractor.api_doc_extractor')->all('eai_v0');

        $factory = new BuilderFactory();

        $stmtClass = $factory->class($className)
            ->extend('BifrostClient')
            ->setDocComment(
                "/**
                  * Class $className
                  */"
            );

        $uses = [];
        $methods = $this->buildApiMethodsList($extractedDoc);
        foreach ($methods as $method){

            // create setter section
            $setterParam = $factory->param("entity");
            $setterParam->setTypeHint($method["objectType"]);

            $class = new \ReflectionClass($method["objectType"]);
            $uses[$method["objectType"]] = $factory->use($class->getName())->as($method["objectType"]);

            $stmtClass->addStmt(
                $factory->method($method["methodName"])
                    ->makePublic()
                    ->addParam($setterParam)
                    ->addStmt(
                        new Return_(
                            new MethodCall(
                                new Variable('this'),
                                "request",
                                [
                                    new String_($method["httpMethod"]),
                                    new String_($method["uri"]),
                                    new Variable('entity'),
                                ]
                            )
                        )
                    )
            );
        }

        $node = $factory->namespace($namespace)
            ->addStmt($factory->use('JMS\Serializer\Annotation')->as('JMS'))
            ->addStmt($factory->use('BifrostClient\BifrostClient'))
            ->addStmts($uses)
            ->addStmt($stmtClass)
            ->getNode();

        $prettyPrinter = new Standard();

        $content = $prettyPrinter->prettyPrintFile([$node]);

        $file = fopen($this->getContainer()->getParameter('kernel.root_dir')."/../".$className.".php", 'wb');
        fwrite($file, $content);
        fclose($file);
    }

    /**
     * @param $extractedDoc
     *
     * @return array
     */
    public function buildApiMethodsList($extractedDoc)
    {
        $results = [];
        foreach ($extractedDoc as $method){
            $uri = $method["resource"];
            $annotation = $method["annotation"];

            $row = [
                "httpMethod" => $annotation->getMethod(),
                "uri" => $uri,
                "methodName" => $annotation->getRoute()->getDefaults()["methodName"],
                "objectType" => $annotation->getInput()["class"]
            ];

            $results[] = $row;
        }
        return $results;
    }
}
