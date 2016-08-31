<?php

namespace Smartbox\ApiBundle\Tests\Command;

use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use Smartbox\ApiBundle\Tests\Fixtures\Entity\Box;

class ClientGeneratorCommandTest extends CommandTestCase
{
    public function testDefaultValue()
    {
        $client = self::createClient();
        $dir = __DIR__;
        $filename =  $dir."/SdkV0SDK.php";
        $this->runCommand($client, "smartbox:api:generateSDK sdk v0 Smartbox\\\ApiBundle\\\Tests\\\Command  -O $dir/ ");

        include $filename;

        $reflection =  new \ReflectionClass(SdkV0SDK::class);

        $namespace = $reflection->getNamespaceName();
        $this->assertEquals("Smartbox\\ApiBundle\\Tests\\Command", $namespace);
        $this->assertEquals("SdkV0SDK", $reflection->getShortName());
        $this->assertEquals(4, count($reflection->getMethods()));

        unlink($filename);
    }

    public function testGeneratedClass()
    {
        $client = self::createClient();
        $dir = __DIR__;
        $filename =  $dir."/SDKTest.php";
        $output = $this->runCommand($client, "smartbox:api:generateSDK sdk v0 Smartbox\\\ApiBundle\\\Tests\\\Command  -O -D $dir/ -C SDKTest -E EmptyClass ");

        $this->assertNotNull($output);

        file_put_contents($filename, $output);

        include $filename;

        $reflection =  new \ReflectionClass(SDKTest::class);

        $namespace = $reflection->getNamespaceName();
        $this->assertEquals("Smartbox\\ApiBundle\\Tests\\Command", $namespace);
        $this->assertEquals("SDKTest", $reflection->getShortName());
        $this->assertEquals("Smartbox\\ApiBundle\\Tests\\Command\\EmptyClass", $reflection->getParentClass()->getName());
        $this->assertEquals(4, count($reflection->getMethods()));
        $methodTestWithBody = $reflection->getMethod("testWithBody");
        $expectedComment =
            <<<EOT
            /**
    * Method to send an entity
    *
    * @param Box \$entity 
    * @param array \$headers 
    *
    * @return mixed|\\Psr\\Http\\Message\\ResponseInterface
    */
EOT;
        $this->assertEquals( 0, strpos($methodTestWithBody->getDocComment(), $expectedComment));

        $this->assertNotNull($methodTestWithBody);

        $parameters = $methodTestWithBody->getParameters();
        $this->assertEquals("entity", $parameters["0"]->getName());
        $this->assertEquals(Box::class, $parameters["0"]->getClass()->name);
        $this->assertTrue($parameters["1"]->isOptional());
        $this->assertTrue($parameters["1"]->isArray());


        $methodTestWithRequirements = $reflection->getMethod("testWithRequirements");

        $parameters = $methodTestWithRequirements->getParameters();
        $this->assertEquals("id", $parameters["0"]->getName());
        $this->assertTrue($parameters["1"]->isOptional());
        $this->assertTrue($parameters["1"]->isArray());


        $methodTestWithRequirements = $reflection->getMethod("testWithFilters");

        $parameters = $methodTestWithRequirements->getParameters();
        $this->assertEquals(4, count($parameters));
        $this->assertEquals("size", $parameters["0"]->getName());
        $this->assertEquals("limit", $parameters["1"]->getName());
        $this->assertEquals("page", $parameters["2"]->getName());
        $this->assertTrue($parameters["3"]->isOptional());
        $this->assertTrue($parameters["3"]->isArray());


        $methodTestWithHeadersAndFiltersAndBody = $reflection->getMethod("testWithHeadersAndFiltersAndBody");

        $parameters = $methodTestWithHeadersAndFiltersAndBody->getParameters();
        $this->assertEquals(8, count($parameters));

        $this->assertEquals("size", $parameters["0"]->getName());

        $this->assertEquals("limit", $parameters["1"]->getName());

        $this->assertEquals("page", $parameters["2"]->getName());

        $this->assertEquals("entity", $parameters["3"]->getName());
        $this->assertEquals(Box::class, $parameters["3"]->getClass()->name);

        $this->assertEquals("id", $parameters["4"]->getName());

        $this->assertEquals("brand", $parameters["5"]->getName());
        $this->assertTrue($parameters["5"]->isOptional());

        $this->assertEquals("country", $parameters["6"]->getName());
        $this->assertTrue($parameters["6"]->isOptional());

        $this->assertEquals("headers", $parameters["7"]->getName());
        $this->assertTrue($parameters["7"]->isOptional());
        $this->assertTrue($parameters["7"]->isArray());

        unlink($filename);
    }

}