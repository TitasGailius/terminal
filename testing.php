<?php

use TitasGailius\Terminal\Terminal;

require __DIR__.'/vendor/autoload.php';

Terminal::executeInBackground('sleep 5');

$response = Shell::execute('sleep 5');

var_dump($response);
