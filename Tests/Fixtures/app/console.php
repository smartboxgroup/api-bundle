<?php
// File: Tests/app/console.php

set_time_limit(0);

require_once __DIR__.'/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('test', true);
$application = new Application($kernel);
$application->run();