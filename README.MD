# Terminal

An Elegent wrapper around Symfony's Process component.

## Installation

```composer require titasgailius/terminal```

## Executing Commands
To execute a command, you may use the `execute` or `executeInBackground` methods. First, let's examine how to execute a basic shell command.

```php
Terminal::executeInBackground('rm -rf vendor');

$response = Terminal::execute('rm -rf vendor');
```

The `execute` method returns an instance of `TitasGailius\Shell\Response`, which provides a variety of methods that may be used to inspect the response:
```php
(array) $response : array;
$response->toArray() : array;

(string) $response : string;
$response->toString() : string;

$response->ok() : bool;
$response->successful() : bool;

$response->code() : int;
```

## Retries

If you would like to automatically retry a script when an error occurs, you may use the `retry` method.
The `retry` method accepts two arguments: the number of times the request should be attempted
and the number of milliseconds that Laravel should wait in between attempts:

```php
Terminal::retries(3, 100)->execute('rm -rf vendor');
```

## Environment Variables

By default, the shell script is run with the same environment variables as the current PHP process.
If you would like to run a script with a different set of environment variables, you may use the `withEnvironmentVariables` method.
The `withEnvironmentVariables` method accepts an array with key-value pairs of the environment variables.

```php
Terminal::withEnvironmentVariables([
    'DIRECTORY' => 'vendor',
])->execute('rm -rf $DIRECTORY');
```

## Command

```php
$command = Shell::command('rm -rf vendor');

if ($inBackground) {
    $command->inBackground();
}

$command->execute();
```