<?php

namespace Smartbox\ApiBundle\Command;

use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ConstFetch;
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouteCollection;

class ClientGeneratorCommand extends ContainerAwareCommand
{

    const DEFAULT_NAMESPACE     = "Smartbox\\ApiRestClient\\Clients";
    const DEFAULT_SDK_FOLDER    = "/ApiRestClient/src/Smartbox/ApiRestClient/";
    const DEFAULT_CLASS_EXTEND  = "ApiRestInternalClient";

    const SIMPLE_ENTITY         = "entity";
    const ARRAY_ENTITY          = "entities";

    const OPTION_OUTPUT         = "output";
    const OPTION_VERSION        = "apiVersion";
    const OPTION_NAMESPACE      = "namespace";
    const OPTION_API            = "api";
    const OPTION_EXTENDS        = "extends";
    const OPTION_DUMP           = "dump";
    const OPTION_BUILT          = "built";

    const CLASS_SUFFIX          = "Client";

    /**
     * @var array
     */
    protected $uses = [];

    /**
     * @var bool
     */
    protected $dump;

    /**
     * @var OutputInterface
     */
    protected $outputInterface;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $outputPath = getcwd() . DIRECTORY_SEPARATOR;
        $defaultExtends = self::DEFAULT_CLASS_EXTEND;
        $defaultNamespace = self::DEFAULT_NAMESPACE;
        $defaultDump = false;
        $defaultBuilt = false;

        $this
            ->setDescription('Generate SDK for a given API')
            ->addOption(self::OPTION_NAMESPACE,"N", InputOption::VALUE_OPTIONAL, 'The namespace of the generated classes', $defaultNamespace)
            ->addOption(self::OPTION_API, "A", InputOption::VALUE_OPTIONAL, 'The name of the api to generate the SDK from', "")
            ->addOption(self::OPTION_VERSION, "F", InputOption::VALUE_OPTIONAL, 'The version of the API to use to generate the SDK', "") //The shortcut is -F because -V is already used and Fassung in German means Version
            ->addOption(self::OPTION_OUTPUT, "O", InputOption::VALUE_OPTIONAL, 'The output path in which the php class/SDK will be created', $outputPath)
            ->addOption(self::OPTION_EXTENDS, 'E', InputOption::VALUE_OPTIONAL, 'The name of the class to extends', $defaultExtends)
            ->addOption(self::OPTION_BUILT, 'B', InputOption::VALUE_OPTIONAL, 'Built the full client', $defaultBuilt)
            ->addOption(self::OPTION_DUMP, 'D', InputOption::VALUE_OPTIONAL, 'Dump the file in the console instead of writing it in files (do not print help also, does not work with the build option)', $defaultDump)
            ->setName('smartbox:api:generateSDK');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dump = $input->getOption(self::OPTION_DUMP);

        $io = new SymfonyStyle($input, $output);
        if(!$this->dump) {
            $io->title('SDK Generator');
        }

        $kernelRootDir = $this->getContainer()->getParameter('kernel.root_dir');

        $this->outputInterface = $output;

        $namespace = $input->getOption(self::OPTION_NAMESPACE);

        $apiName = $input->getOption(self::OPTION_API);

        $version = $input->getOption(self::OPTION_VERSION);
        if((empty($version) && !empty($apiName) )|| (empty($apiName) && !empty($version) ) ){
            throw new \LogicException("You need to specify both api version and api name.");
        }

        $built = $input->getOption(self::OPTION_BUILT);
        if($built && $this->dump){
            throw new \LogicException("Cannot generate the SDK and dump in the console the clients at the same time.");
        }

        $classToExtend = $input->getOption(self::OPTION_EXTENDS);

        //Get all the routes to be able to match ApiMethod to correct path
        $this->routes = $this->getContainer()->get('router')->getRouteCollection();

        $outputPath = str_replace('%kernel.root_dir%', $kernelRootDir, $input->getOption('output'));
        if (!preg_match('/' . preg_quote("/", '/') . '$/', $outputPath)){
            $outputPath .= "/";
        }
        if(!file_exists($outputPath)){
            throw new \Exception("Folder $outputPath doesn't exists.");
        }

        $apiConfigurator = $this->getContainer()->get("smartapi.configurator");

        if(!empty($apiName) && !empty($version)){
            $services["$apiName"]["methods"] = $apiConfigurator->getConfigByServiceNameAndVersion($apiName, $version);
            $services["$apiName"]["version"] = $version;
            $services["$apiName"]["name"] = $apiName;
        }else{
            $services = $apiConfigurator->getConfig();
        }

        $generatedFilePaths = [];
        foreach ($services as $service ){
            if(!$this->dump){
                $io->section(sprintf('Generating REST client for API %s with version %s', $service["name"], $service["version"] ));
            }

            $generatedFilePaths[] = $this->buildClass(
                $service,
                $namespace,
                $outputPath,
                $classToExtend
            );
        }

        if ($built && !$this->dump){
            $output->writeln(sprintf('<info>Building full SDK with the %s generated clients.</info>', count($generatedFilePaths)));
            $this->buildFullSDK($generatedFilePaths, $outputPath);
        }
    }

    /**
     * Generate a PHP class for a given API service
     *
     * @param array $service
     * @param $namespace
     * @param $outputPath
     * @param string $classToExtend
     *
     * @return string
     */
    private function buildClass(array $service, $namespace, $outputPath, $classToExtend)
    {
        $dump = $this->dump;
        $apiName = $service["name"];
        $version = $service["version"];
        $methods = $service["methods"];

        $className = ucfirst($apiName).ucfirst($version).self::CLASS_SUFFIX;

        $factory = new BuilderFactory();
        $stmtClass = $factory->class($className)
            ->setDocComment(
                "\r\n/**
                  * Class $className
                  */"
            );

        if(!empty($classToExtend)){
            $stmtClass->extend($classToExtend);
        }

        //Build all the methods of the given API
        foreach ($methods as $methodName=>$method){
            if(!$dump){
                $this->outputInterface->writeln(sprintf('<info>Generating method %s.</info>', $methodName));
            }

            $sdkMethod = $this->buildMethod($methodName, $method, $factory, $apiName, $version);
            $stmtClass->addStmt( $sdkMethod );
        }

        $node = $factory->namespace($namespace)
            ->addStmt($factory->use('JMS\Serializer\Annotation')->as('JMS'))
            ->addStmt($factory->use('Smartbox\ApiRestClient\ApiRestInternalClient'))
            ->addStmt($factory->use('Smartbox\ApiRestClient\ApiRestResponse'))
            ->addStmts($this->uses)
            ->addStmt($stmtClass)
            ->getNode();

        $this->uses = [];

        $prettyPrinter = new Standard();
        $content = $prettyPrinter->prettyPrintFile([$node]);

        if(!$dump){
            $filePath = $outputPath.$className.".php";
            $file = fopen($filePath, 'wb');
            fwrite($file, $content);
            fclose($file);
            $this->outputInterface->writeln(sprintf('<info>%s methods have been generated in file %s.</info>',count($methods), $filePath));
            return $filePath;
        }else{
            $this->outputInterface->writeln($content);
            return null;
        }
    }

    /**
     * Build PHP method for a given API method
     *
     * @param $methodName
     * @param $apiMethod
     * @param BuilderFactory $factory
     * @param $apiName
     * @param $version
     *
     * @return Method
     * @throws \Exception
     */
    protected function buildMethod($methodName, $apiMethod, BuilderFactory &$factory, $apiName, $version)
    {
        $methodArgs = [];
        $requestArgs = [];
        $requirements = [];
        $filters = [];
        $description = $apiMethod["description"];
        $methodComment = "/**\r\n* $description\r\n*\r\n";

        $entityArgument = new ConstFetch(new Name('null'));
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
                    $entityArgument = new Variable($entityVariableName);

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

                    $param = $factory->param($inputName);
                    $param->setDefault( new ConstFetch(new Name('null')));
                    $methodArgs[] = $param;

                    $filters[] = new ArrayItem($filter, new String_($inputName));

                    $comment = "* @param $type \$$inputName \r\n";
                    break;
                default:
                    throw new \Exception("Unknown input mode $inputMode");
            }
            $methodComment .= $comment;
        }
        //Generate the URI for the rest call
        $uri = $this->generateURI($methodName, $requirements, $apiName, $version);
        $methodContent = [
            new Assign(new Variable("uri"), $uri),
        ];

        $calledMethodArgs = [
            new String_($apiMethod["rest"]["httpMethod"]),
            new Variable("uri"),
            $entityArgument
        ];

        if(!empty($filters)){
            //Creating a new line in the method to merge the filters into one array
            $methodContent[] = new Assign(new Variable("filters"), new Array_($filters));
            $calledMethodArgs = array_merge($calledMethodArgs, [new Variable("filters")]);
        }else{
            //Define filters as empty array
            $filtersArgument = new Array_();
            $calledMethodArgs = array_merge($calledMethodArgs, [$filtersArgument]);
        }

        if(!empty($apiMethod["headers"])){
            $items = [];
            foreach ($apiMethod["headers"] as $header){
                $headerVariable = new Variable($header);

                $methodArgs[] = $factory->param($header);

                $methodComment .= "* @param string \$$header \r\n";

                $items[] = new ArrayItem($headerVariable, new String_($header));
            }
            //Creating a new line in the method to merge the headers with the existing header argument
            $methodContent[] = new Assign(new Variable("customHeaders"), new Array_($items));
            $methodContent[] = new Assign(new Variable("headers"), new FuncCall( new Name("array_merge"), [new Variable("customHeaders"), new Variable("headers")]));
        }


        $calledMethodArgs = array_merge($calledMethodArgs, $requestArgs, [new Variable("headers")]);

        if(!empty($apiMethod["output"])){
            //Manage deserialization type
            $outputType  = $apiMethod["output"]["type"];
            if(self::isArray($outputType)){
                $outputType = ApiConfigurator::getSingleType($outputType);
                $outputType = sprintf("array<%s>", $outputType);
            }
            $calledMethodArgs[] = new String_($outputType);
        }

        //Add return line to the method
        $methodContent[] = new Return_(
            new MethodCall(
                new Variable('this'),
                "request",
                $calledMethodArgs
            )
        );

        //Add default headers param
        $headers = $factory->param("headers");
        $headers->setDefault(new Array_());
        $headers->setTypeHint("array");
        $methodArgs[] = $headers;
        $methodComment .=
            "* @param array \$headers \r\n";

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
              * @return ApiRestResponse
              */";

        $sdkMethod->setDocComment($methodComment);

        return $sdkMethod;
    }

    /**
     * Return the path of a given API method
     *
     * @param $methodName
     * @param $parameters
     * @param $apiName
     * @param $version
     *
     * @return FuncCall|String_
     * @throws \Exception
     */
    protected function generateURI($methodName, $parameters, $apiName, $version)
    {
        $path = $this->routes->get(sprintf("smartapi.rest.%s_%s.%s", $apiName, $version, $methodName))->getPath();

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
     * Generate the files and folder for a the full SDK
     *
     * @param array $generatedFilePaths
     * @param $outputPath
     *
     * @throws \Exception
     */
    private function buildFullSDK(array $generatedFilePaths, $outputPath )
    {
        $dir = $outputPath.self::DEFAULT_SDK_FOLDER;

        $clientsDir = $dir."Clients/";
        if(!file_exists($clientsDir)){
            mkdir($clientsDir, 0777, true);
        }
        foreach ($generatedFilePaths as $path){
            if(file_exists($path)){
                copy($path, $clientsDir.basename($path));
                $this->outputInterface->writeln(sprintf('<info>Removing file %s.</info>', $path));
                unlink($path);
            }else{
                throw new \Exception("File $path doesn't exists");
            }
        }

        $this->copyToDir(__DIR__."/SDK/*",$dir);
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

    /**
     * @param $pattern
     * @param $dir
     */
    private function copyToDir($pattern, $dir)
    {
        foreach (glob($pattern) as $file) {
            if(!is_dir($file) && is_readable($file)) {
                $destination = $dir . basename($file);
                copy($file, $destination);
            }
        }
    }
}
