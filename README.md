# VarDump

PHP Helper library to print variables for debugging purposes.

## Usage

```php
<?php

// dumps a variable
vd("any variable");

// or multiple variables, as many as your memory can handle
vd("any variable", "any other variable");

// dumps a variable and dies
vdd("any variable");

// or with multiple variables
vdd("any variable", "any other variable");

// dumps one variable with a specific configuration
// set any configuration parameter to null to use the global value
$maxRecursiveDepth = 10;
$maxStringLength = 100;
$includeMethods = true;
$theme = new SpidermanHtmlVarDumpTheme();

vdc("any variable", $maxRecursiveDepth, $maxStringLength, $includeMethods, $theme);

// dumps one variable with a specific configuration and dies
vdcd("any variable", $maxRecursiveDepth, $maxStringLength, $includeMethods, $theme);

// as called for the sample screenshots
vd([
    null,
    true,
    42,
    3.1415,
    "any string",
    new Exception(),
    fopen('php://stdout', 'w'),
]);
```

## Screenshots

Output on a HTML page:

![Screenshot HTML](screenshot-html.png "Screenshot HTML")

Output in a CLI:

![Screenshot CLI](screenshot-cli.png "Screenshot CLI")

## Configuration

You can use the environment variable to configure the vardump.

```php
<?php

// Flag to see if object methods should be included
$_ENV['VAR_DUMP_METHODS'] = true;

// Maximum depth for arrays and objects
$_ENV['VAR_DUMP_RECURSIVE_DEPTH'] = 10;

// Maximum length for the preview of a string
$_ENV['VAR_DUMP_STRING_LENGTH'] = 100;

// Name of the CLI theme class
$_ENV['VAR_DUMP_THEME_CLI'] = 'CliVarDumpTheme';

// Name of the HTML theme class, choose between:
// - HtmlVarDumpTheme
// - BatmanHtmlVarDumpTheme
// - HulkHtmlVarDumpTheme,
// - IronmanHtmlVarDumpTheme,
// - SpidermanHtmlVarDumpTheme,
// - SupermanHtmlVarDumpTheme,
$_ENV['VAR_DUMP_THEME_HTML'] = 'SpidermanHtmlVarDumpTheme';
```

### Dump To File

When you are debugging a web application, output can break your layout or response, especially when developing a restful API.

You can easily pipe the vardump output to a file using the file theme.

```php
<?php

// the file to dump to
$file = __DIR__ . '/vardump.log';

// create a theme to log to the file
$theme = new FileVarDumpTheme($file);

// you can also provide a truncate size in KB, defaults to 1MB
$theme = new FileVarDumpTheme($file, 4096); // 4MB

// set the theme for both environments
$_ENV['VAR_DUMP_THEME_CLI'] = $theme;
$_ENV['VAR_DUMP_THEME_HTML'] = $theme;
```

Once the log file is created, you can use the ```tail``` command to keep an eye on it:

```
tail -f vardump.log
```

## Installation

You can use [Composer](http://getcomposer.org) to install this helper into your project.

```
composer require kayalion/vardump
```

For manual installation, copy the ```src/VarDump.php``` file to your project and include it like:

```php
<?php 

include __DIR__ . '/src/VarDump.php';
```
