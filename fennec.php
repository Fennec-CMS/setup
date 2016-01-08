#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Fennec\Console\Command\SetupCommand;

$commands = [
    new SetupCommand()
];

$application = new Application();
$application->addCommands($commands);
$application->run();
