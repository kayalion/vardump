# VarDump

PHP Helper library to print variables for debugging purposes.

## Usage

```php
<?php

// when you use Composer, this is not necessairy
include __DIR__ . '/src/VarDump.php';

// prints a variable
d("any variable");

// prints a variable and exits
dd("any variable");
```

## Configuration

You can use the environment variable to configure the vardump.

```php
<?php

// Maximum depth for arrays and objects
$_ENV['VAR_DUMP_RECURSIVE_DEPTH'] = 10;

// Maximum length for the preview of a string
$_ENV['VAR_DUMP_STRING_LENGTH'] = 100;

// Name of the CLI theme class, only one implemented
$_ENV['VAR_DUMP_THEME_CLI'] = 'CliVarDumpTheme';

// Name of the Html theme class, choose between:
// - HtmlVarDumpTheme
// - BlueHtmlVarDumpTheme
// - RedHtmlVarDumpTheme,
// - GreenHtmlVarDumpTheme,
// - BrownHtmlVarDumpTheme,
$_ENV['VAR_DUMP_THEME_HTML'] = 'HtmlVarDumpTheme';
```

## Installation

You can use [Composer](http://getcomposer.org) to install this helper into your project.

```
composer require kayalion/vardump
```
