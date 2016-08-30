<?php

namespace Smartbox\ApiBundle\Command;

use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClientGeneratorCommand extends ContainerAwareCommand
{
    const ARRAY_PREFIX      = "array<";

    const SIMPLE_ENTITY     = "entity";
    const ARRAY_ENTITY      = "entities";

    const OPTION_OUTPUT     = "output";
    const OPTION_VERSION    = "apiVersion";
    const OPTION_CLASS_NAME = "className";
    const OPTION_NAMESPACE  = "namespace";
    const OPTION_API        = "api";
    const OPTION_EXTENDS    = "extends";

    protected $uses = [];
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $outputPath = getcwd() . DIRECTORY_SEPARATOR;
        $defaultApiName = "default";
        $defaultClassName = "";
        $defaultExtends = "";

        $this
            ->setDescription('Generate SDK for a given API')
            ->addArgument(self::OPTION_NAMESPACE, InputArgument::REQUIRED, 'The namespace of the generated class')
            ->addArgument(self::OPTION_VERSION, InputArgument::REQUIRED, 'The version of the API to use to generate the SDK')
            ->addOption(self::OPTION_API, "A", InputOption::VALUE_OPTIONAL, 'The name of the api to generate the SDK from', $defaultApiName)
            ->addOption(self::OPTION_OUTPUT, "O", InputOption::VALUE_OPTIONAL, 'The output path in which the php class will be created', $outputPath)
            ->addOption(self::OPTION_CLASS_NAME, 'C', InputOption::VALUE_OPTIONAL, 'The name of the class that will be generated', $defaultClassName)
            ->addOption(self::OPTION_EXTENDS, 'E', InputOption::VALUE_OPTIONAL, 'The name of the class to extends', $defaultExtends)
            ->setName('smartbox:api:generateSDK');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernelRootDir = $this->getContainer()->getParameter('kernel.root_dir');
        $outputPath = str_replace('%kernel.root_dir%', $kernelRootDir, $input->getOption('output'));
        if(!file_exists($outputPath)){
            throw new \Exception("Folder $outputPath doesn't exists.");
        }

        $namespace = $input->getArgument(self::OPTION_NAMESPACE);

        $version = $input->getArgument(self::OPTION_VERSION);

        $apiName = $input->getOption(self::OPTION_API);

        $className = $input->getOption(self::OPTION_CLASS_NAME);
        if(empty($className)){
            $className = ucfirst($apiName)."V".$version."SDK";
        }

        $classToExtend = $input->getOption(self::OPTION_EXTENDS);

        $extractedDoc = $this->getContainer()->get('nelmio_api_doc.extractor.api_doc_extractor')->all($apiName);

        $factory = new BuilderFactory();
        $stmtClass = $factory->class($className)
            ->setDocComment(
                "/**
                  * Class $className
                  */"
            );
        if(!empty($classToExtend)){
            $stmtClass->extend($classToExtend);
        }

        $uses = [];
        $methods = $this->buildApiMethodsList($extractedDoc);

        foreach ($methods as $method){
            $sdkMethod = $this->buildMethod($method, $factory);
            $stmtClass->addStmt( $sdkMethod );
        }

        $node = $factory->namespace($namespace)
            ->addStmt($factory->use('JMS\Serializer\Annotation')->as('JMS'))
            ->addStmts($this->uses)
            ->addStmt($stmtClass)
            ->getNode();

        $prettyPrinter = new Standard();

        $content = $prettyPrinter->prettyPrintFile([$node]);

        $file = fopen($outputPath.$className.".php", 'wb');
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
                "uri" => $uri,
                "httpMethod" => $annotation->getMethod(),
                "methodName" => $annotation->getRoute()->getDefaults()["methodName"],
                "objectType" => $annotation->getInput()["class"],
                "requirements" => $annotation->getRequirements()
            ];

            $results[] = $row;
        }
        return $results;
    }

    /**
     * @param $type
     *
     * @return bool
     */
    protected static function isArray($type)
    {
        if( strpos($type, self::ARRAY_PREFIX) === false) {
            return false;
        }
        return true;
    }

    /**
     * @param $type
     *
     * @return string
     */
    protected function extractTypeFromArray($type){
        if(self::isArray($type)){
            $end = strpos($type, ">");
            $type = substr($type, strlen(self::ARRAY_PREFIX), $end - strlen(self::ARRAY_PREFIX));
        }
        return $type;
    }


    /**
     * @param $apiMethod
     * @param $factory
     *
     * @return mixed
     */
    protected function buildMethod($apiMethod, BuilderFactory &$factory)
    {
        $inputParameters = $apiMethod["objectType"];
        $setterParam = null;
        $requestParam = [];
        $methodComment ="/**\r\n";

        if(!empty($inputParameters)){

            $entityVariableName = self::SIMPLE_ENTITY;
            $type = $this->extractTypeFromArray($inputParameters);

            $class = new \ReflectionClass($type);
            $shortNameClass = $class->getShortName();

            $hint = $shortNameClass;

            $comment = "* @param ".$shortNameClass." \$$entityVariableName \r\n";

            if(self::isArray($inputParameters)){
                $entityVariableName = self::ARRAY_ENTITY;
                $hint = "array";
                $comment = "* @param ".$shortNameClass."[] \$$entityVariableName \r\n";
            }

            // Creating new param
            $setterParam = $factory->param($entityVariableName);
            $setterParam->setTypeHint($hint);

            //Adding it to the list of param
            $setterParams[] = $setterParam;

            //Adding new variable to the list of argument to pass to the this->request method
            $requestParam[] = new Variable($entityVariableName);

            //Add class to the list of dependencies
            $this->uses[$type] = $factory->use($class->getName())->as($shortNameClass);
            $methodComment .= $comment;
        }

        foreach ($apiMethod["requirements"] as $entityVariableName=>$requirement){
            $param = $factory->param($entityVariableName);
            $setterParams[] = $param;
            $type = $requirement["dataType"];
            $methodComment .= "* @param $type \$$entityVariableName \r\n";
        }

        $uri = $this->generateURI($apiMethod["uri"], $apiMethod["requirements"]);
        $requestMethodParameters = [
            new String_($apiMethod["httpMethod"]),
            new Variable("uri")
        ];

        $headers = $factory->param("headers");
        $headers->setDefault(new Array_());
        $setterParams[] = $headers;
        $methodComment .=
            "* @param array \$headers \r\n";
        $requestMethodParameters = array_merge($requestMethodParameters, $requestParam, [new Variable("headers")]);

        $sdkMethod = $factory->method($apiMethod["methodName"])
            ->makePublic()
            ->addStmt(
                new Assign(new Variable("uri"), $uri)
            )
            ->addStmt(
                new Return_(
                    new MethodCall(
                        new Variable('this'),
                        "request",
                        $requestMethodParameters
                    )
                )
            );

        if(!empty($setterParams)){
            foreach ($setterParams as $param){
                $sdkMethod->addParam($param);
            }
        }

        $methodComment .=
             "*
              * @return mixed|\\Psr\\Http\\Message\\ResponseInterface
              */";

        $sdkMethod->setDocComment($methodComment);
        return $sdkMethod;
    }

    /**
     * @param $uri
     * @param $parameters
     *
     * @return FuncCall|String_
     */
    protected function generateURI($uri, $parameters)
    {
        if(empty($parameters)){
            return new String_($uri);
        }

        foreach ($parameters as $name=>$requirement){
            $position = strpos($uri, "{".$name."}");
            $arguments[$position] = new Variable($name);
            $uri = str_replace("{".$name."}", "%s", $uri);
        }

        // Sort arguments by appearance in the URI
        ksort($arguments);

        $sprintfArgs = array_merge( [new String_( $uri ) ], $arguments);

        return new FuncCall( new Name("sprintf"), $sprintfArgs);
    }

}
