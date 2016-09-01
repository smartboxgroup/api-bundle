<?php

namespace Smartbox\ApiBundle\Tests\Command;

use Smartbox\ApiBundle\Tests\Fixtures\Entity\Box;

class ClientGeneratorCommandTest extends CommandTestCase
{
    protected $fileToRemove;

    public function testDefaultValue()
    {
        $client = self::createClient();
        $dir = __DIR__;
        $this->fileToRemove =  $dir."/SdkV0Client.php";
        $this->runCommand($client, "smartbox:api:generateSDK -N Smartbox\\\ApiBundle\\\Tests\\\Command -O $dir/ -A sdk -F v0 -E EmptyClass");

        include_once $this->fileToRemove;

        $reflection =  new \ReflectionClass(SdkV0Client::class);

        $namespace = $reflection->getNamespaceName();
        $this->assertEquals("Smartbox\\ApiBundle\\Tests\\Command", $namespace);
        $this->assertEquals("SdkV0Client", $reflection->getShortName());
        $this->assertEquals(5, count($reflection->getMethods()));
    }

    public function testGeneratedClass()
    {
        $client = self::createClient();
        $dir = __DIR__;
        $this->fileToRemove =  $dir."/SdkV0Client.php";

        $output = $this->runCommand($client, "smartbox:api:generateSDK -N Smartbox\\\ApiBundle\\\Tests\\\Command  -D true -O $dir/ -A sdk -F v0 -E EmptyClass ");

        $this->assertNotNull($output);

        file_put_contents($this->fileToRemove, $output);

        include_once $this->fileToRemove;

        $reflection =  new \ReflectionClass(SdkV0Client::class);

        $namespace = $reflection->getNamespaceName();
        $this->assertEquals("Smartbox\\ApiBundle\\Tests\\Command", $namespace);
        $this->assertEquals("SdkV0Client", $reflection->getShortName());
        $this->assertEquals("Smartbox\\ApiBundle\\Tests\\Command\\EmptyClass", $reflection->getParentClass()->getName());
        $this->assertEquals(5, count($reflection->getMethods()));
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


        $methodTestWithArrayBody = $reflection->getMethod("testWithArrayBody");

        $parameters = $methodTestWithArrayBody->getParameters();
        $this->assertEquals("entities", $parameters["0"]->getName());
        $this->assertTrue($parameters["0"]->isArray());
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
    }

    /**
     * Remove the file that has been generated
     */
    protected function tearDown()
    {
        if(file_exists($this->fileToRemove)){
            unlink($this->fileToRemove);
        }
        parent::tearDown();
    }

}