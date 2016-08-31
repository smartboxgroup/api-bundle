<?php

namespace Smartbox\ApiBundle\Command;

use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;
use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouteCollection;

class ClientGeneratorCommand extends ContainerAwareCommand
{
    const SIMPLE_ENTITY     = "entity";
    const ARRAY_ENTITY      = "entities";

    const OPTION_OUTPUT     = "output";
    const OPTION_VERSION    = "apiVersion";
    const OPTION_CLASS_NAME = "className";
    const OPTION_NAMESPACE  = "namespace";
    const OPTION_API        = "api";
    const OPTION_EXTENDS    = "extends";
    const OPTION_DUMP       = "dump";

    /**
     * @var array
     */
    protected $uses = [];

    /**
     * @var string
     */
    protected $apiName;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var bool
     */
    protected $dumpOption;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $outputPath = getcwd() . DIRECTORY_SEPARATOR;
        $defaultClassName = "";
        $defaultExtends = "";
        $defaultDump = false;

        $this
            ->setDescription('Generate SDK for a given API')
            ->addArgument(self::OPTION_API,InputArgument::REQUIRED, 'The name of the api to generate the SDK from')
            ->addArgument(self::OPTION_VERSION, InputArgument::REQUIRED, 'The version of the API to use to generate the SDK')
            ->addArgument(self::OPTION_NAMESPACE, InputArgument::REQUIRED, 'The namespace of the generated class')
            ->addOption(self::OPTION_OUTPUT, "O", InputOption::VALUE_OPTIONAL, 'The output path in which the php class will be created', $outputPath)
            ->addOption(self::OPTION_CLASS_NAME, 'C', InputOption::VALUE_OPTIONAL, 'The name of the class that will be generated', $defaultClassName)
            ->addOption(self::OPTION_EXTENDS, 'E', InputOption::VALUE_OPTIONAL, 'The name of the class to extends', $defaultExtends)
            ->addOption(self::OPTION_DUMP, 'D', InputOption::VALUE_OPTIONAL, 'Dump the file in the console instead of writing it in files (do not print help also)', $defaultDump)
            ->setName('smartbox:api:generateSDK');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Get all the routes to be able to match ApiMethod to correct path
        $this->routes = $this->getContainer()->get('router')->getRouteCollection();

        $kernelRootDir = $this->getContainer()->getParameter('kernel.root_dir');
        $outputPath = str_replace('%kernel.root_dir%', $kernelRootDir, $input->getOption('output'));
        if(!file_exists($outputPath)){
            throw new \Exception("Folder $outputPath doesn't exists.");
        }

        $namespace = $input->getArgument(self::OPTION_NAMESPACE);

        $this->version = $input->getArgument(self::OPTION_VERSION);

        $this->apiName = $input->getArgument(self::OPTION_API);

        $dump = $input->getOption(self::OPTION_DUMP);

        if(!$dump){
            $output->writeln('<info>Generating REST client for API '.$this->apiName.' with version '.$this->version.'</info>');
        }

        $className = $input->getOption(self::OPTION_CLASS_NAME);
        if(empty($className)){
            $className = ucfirst($this->apiName).ucfirst($this->version)."SDK";
        }

        $apiConfigurator = $this->getContainer()->get("smartapi.configurator");
        $methods = $apiConfigurator->getConfigByServiceNameAndVersion($this->apiName, $this->version);

        $factory = new BuilderFactory();
        $stmtClass = $factory->class($className)
            ->setDocComment(
                "\r\n/**
                  * Class $className
                  */"
            );

        $classToExtend = $input->getOption(self::OPTION_EXTENDS);
        if(!empty($classToExtend)){
            $stmtClass->extend($classToExtend);
        }

        //Build all the methods of the given API
        foreach ($methods as $methodName=>$method){
            if(!$dump){
                $output->writeln('<info>Generating method '.$methodName.'</info>');
            }

            $sdkMethod = $this->buildMethod($methodName, $method, $factory);
            $stmtClass->addStmt( $sdkMethod );
        }

        $node = $factory->namespace($namespace)
            ->addStmt($factory->use('JMS\Serializer\Annotation')->as('JMS'))
            ->addStmts($this->uses)
            ->addStmt($stmtClass)
            ->getNode();

        $prettyPrinter = new Standard();
        $content = $prettyPrinter->prettyPrintFile([$node]);

        if(!$dump){
            $filepath = $outputPath.$className.".php";
            $file = fopen($filepath, 'wb');
            fwrite($file, $content);
            fclose($file);
            $output->writeln('<info>'.count($methods).' methods have been generated in file '.$filepath.'</info>');
        }else{
            $output->writeln($content);
        }
    }


    /**
     * Build PHP function for a given API method
     *
     * @param $methodName
     * @param $apiMethod
     * @param BuilderFactory $factory
     *
     * @return Method
     * @throws \Exception
     */
    protected function buildMethod($methodName, $apiMethod, BuilderFactory &$factory)
    {
        $methodArgs = [];
        $requestArgs = [];
        $requirements = [];
        $filters = [];
        $description = $apiMethod["description"];
        $methodComment = "/**\r\n* $description\r\n*\r\n";


        foreach ($apiMethod["input"] as $inputName => $input){
            $inputMode = $input["mode"];

            $type = ApiConfigurator::getSingleType($input["type"]);

            switch ($inputMode){
                case Configuration::MODE_BODY:

                    $class = new \ReflectionClass($type);
                    $shortNameClass = $class->getShortName();

                    if(self::isArray($input["type"])){
                        $entityVariableName = self::ARRAY_ENTITY;
                        $hint = "array";
                        $comment = "* @param ".$shortNameClass."[] \$$entityVariableName \r\n";
                    }else{
                        $entityVariableName = self::SIMPLE_ENTITY;
                        $comment = "* @param ".$shortNameClass." \$$entityVariableName \r\n";
                        $hint = $shortNameClass;
                    }

                    // Creating new param
                    $param = $factory->param($entityVariableName);
                    $param->setTypeHint($hint);

                    //Adding it to the list of param
                    $methodArgs[] = $param;

                    //Adding new variable to the list of argument to pass to the this->request method
                    $requestArgs[] = new Variable($entityVariableName);

                    //Add class to the list of dependencies
                    $this->uses[$type] = $factory->use($class->getName())->as($shortNameClass);

                    break;
                case Configuration::MODE_REQUIREMENT:
                    $requirements[] = $inputName;

                    $methodArgs[] = $factory->param($inputName);

                    $comment = "* @param $type \$$inputName \r\n";
                    break;
                case Configuration::MODE_FILTER:
                    $filter = new Variable($inputName);

                    $methodArgs[] = $factory->param($inputName);

                    $filters[] = new ArrayItem($filter, new String_($inputName));

                    $comment = "* @param $type \$$inputName \r\n";
                    break;
                default:
                    throw new \Exception("Unknown input mode $inputMode");
            }
            $methodComment .= $comment;
        }
        //Generate the URI for the rest call
        $uri = $this->generateURI($methodName, $requirements);
        $methodContent = [
            new Assign(new Variable("uri"), $uri),
        ];

        $calledMethodArgs = [
            new String_($apiMethod["rest"]["httpMethod"]),
            new Variable("uri")
        ];
        $calledMethodArgs = array_merge($calledMethodArgs, $requestArgs, [new Variable("headers")]);

        if(!empty($filters)){
            //Creating a new line in the method to merge the filters into one array
            $methodContent[] = new Assign(new Variable("filters"), new Array_($filters));
            $calledMethodArgs = array_merge($calledMethodArgs, [new Variable("filters")]);
        }

        if(!empty($apiMethod["headers"])){
            $items = [];
            foreach ($apiMethod["headers"] as $header){
                $headerVariable = new Variable($header);

                $param = $factory->param($header);
                $param->setDefault(new String_(""));
                $methodArgs[] = $param;

                $methodComment .= "* @param string \$$header \r\n";

                $items[] = new ArrayItem($headerVariable, new String_($header));
            }
            //Creating a new line in the method to merge the headers with the existing header argument
            $methodContent[] = new Assign(new Variable("customHeaders"), new Array_($items));
            $methodContent[] = new Assign(new Variable("headers"), new FuncCall( new Name("array_merge"), [new Variable("customHeaders"), new Variable("headers")]));
        }

        //Add default headers param
        $headers = $factory->param("headers");
        $headers->setDefault(new Array_());
        $headers->setTypeHint("array");
        $methodArgs[] = $headers;
        $methodComment .=
            "* @param array \$headers \r\n";

        //Add return line to the method
        $methodContent[] = new Return_(
            new MethodCall(
                new Variable('this'),
                "request",
                $calledMethodArgs
            )
        );

        //Building the method
        $sdkMethod = $factory->method($methodName)
            ->makePublic()
            ->addStmts(
                $methodContent
            );

        //Add all the arguments to the methods
        foreach ($methodArgs as $param){
            $sdkMethod->addParam($param);
        }

        $methodComment .=
            "*
              * @return mixed|\\Psr\\Http\\Message\\ResponseInterface
              */";

        $sdkMethod->setDocComment($methodComment);

        return $sdkMethod;
    }


    /**
     * Return the path of a given API method
     *
     * @param $methodName
     * @param $parameters
     *
     * @return FuncCall|String_
     * @throws \Exception
     */
    protected function generateURI($methodName, $parameters)
    {
        $path = $this->routes->get(sprintf("smartapi.rest.%s_%s.%s", $this->apiName, $this->version, $methodName))->getPath();

        if(empty($path)){
            throw new \Exception("Unable to find a route for method $methodName");
        }
        if(empty($parameters)){
            return new String_($path);
        }

        foreach ($parameters as $requirement){
            $position = strpos($path, "{".$requirement."}");
            $arguments[$position] = new Variable($requirement);
            $uri = str_replace("{".$requirement."}", "%s", $path);
        }

        // Sort arguments by appearance in the URI
        ksort($arguments);
        $sprintfArgs = array_merge( [new String_( $uri ) ], $arguments);

        return new FuncCall( new Name("sprintf"), $sprintfArgs);
    }

    /**
     * Return true if the given dataType is an array
     *
     * @param $type
     *
     * @return bool
     */
    protected static function isArray($type)
    {
        if( strpos($type, "[]") === false) {
            return false;
        }
        return true;
    }
}
