<?php

if (!function_exists('d')) {
    /**
     * Shortcut to print a variable for debug purposes
     * @param mixed $variable1 First variable to print
     * @param mixed $variable2 Second variable to print
     * @param mixed $variable3 ...
     * @return null
     */
    function d() {
        static $varDump;

        if (!$varDump) {
            $varDump = new VarDump();
        }

        $variables = func_get_args();
        foreach ($variables as $variable) {
            $varDump->print($variable);
        }
    }
}

if (!function_exists('dd')) {
    /**
     * Shortcut to print a value for debug purposes. This function will stop your
     * script after the print
     * @param mixed $variable1 First variable to print
     * @param mixed $variable2 Second variable to print
     * @param mixed $variable3 ...
     * @return null
     */
    function dd() {
        call_user_func_array('d', func_get_args());

        exit;
    }
}

if (!function_exists('dc')) {
    /**
     * Shortcut to print a value for debug purposes.
     * @param mixed $variable Variable to print
     * @param integer $recursiveDepth Maximum level of recursiveness
     * @param integer $stringLength Maximum length for the preview of a string
     * @param boolean $includeMethods Flag to see if object methods should be
     * included
     * @param VarDumpTheme $theme Theme for the output
     * @return null
     */
    function dc($variable, $recursiveDepth = null, $stringLength = null, $includeMethods = null, VarDumpTheme $theme = null) {
        $varDump = new VarDump($recursiveDepth, $stringLength, $includeMethods, $theme);
        $varDump->print($variable);
    }
}

/**
 * Class to print variables like var_dump
 */
class VarDump {

    /**
     * Constructs a new dump
     * @param integer $recursiveDepth Maximum level of recursiveness
     * @param integer $stringLength Maximum length for the preview of a string
     * @param boolean $includeMethods Flag to see if object methods should be
     * included
     * @param VarDumpTheme $theme Theme for the output, null for automatic
     * @return null
     */
    public function __construct($recursiveDepth = null, $stringLength = null, $includeMethods = null, VarDumpTheme $theme = null) {
        if ($recursiveDepth === null) {
            $recursiveDepth = isset($_ENV['VAR_DUMP_RECURSIVE_DEPTH']) ? $_ENV['VAR_DUMP_RECURSIVE_DEPTH'] : 10;
        }

        if ($stringLength === null) {
            $stringLength = isset($_ENV['VAR_DUMP_STRING_LENGTH']) ? $_ENV['VAR_DUMP_STRING_LENGTH'] : 100;
        }

        if ($includeMethods === null) {
            $includeMethods = isset($_ENV['VAR_DUMP_METHODS']) ? $_ENV['VAR_DUMP_METHODS'] : true;
        }

        $this->recursiveDepth = 0;
        $this->recursiveMaximum = $recursiveDepth;

        $this->stringLength = $stringLength;
        $this->stringSearch = array("\0", "\a", "\b", "\f", "\n", "\r", "\t", "\v");
        $this->stringReplace = array('\0', '\a', '\b', '\f', '\n', '\r', '\t', '\v');

        $this->includeMethods = $includeMethods;
        $this->objects = array();
        $this->objectId = 0;

        $this->isFirst = true;

        $this->setTheme($theme);
    }

    /**
     * Sets the theme of the dump
     * @param VarDumpTheme $theme Theme to set, null for automatic
     * @return null
     */
    public function setTheme(VarDumpTheme $theme = null) {
        if ($theme == null) {
            if (php_sapi_name() === 'cli') {
                $themeName = isset($_ENV['VAR_DUMP_THEME_CLI']) ? $_ENV['VAR_DUMP_THEME_CLI'] : 'CliVarDumpTheme';
            } else {
                $themeName = isset($_ENV['VAR_DUMP_THEME_HTML']) ? $_ENV['VAR_DUMP_THEME_HTML'] : 'SpidermanHtmlVarDumpTheme';
            }

            $theme = new $themeName();
        }

        $this->theme = $theme;
    }

    /**
     * Prints any value for debug purposes
     * @var mixed $value Value to print
     * @return null
     */
    public function print($value) {
        $output = '';

        if ($this->isFirst) {
            $this->isFirst = false;

            $output .= $this->theme->beforeFirstPrint();
        }

        $output .= $this->theme->beforePrint($this->getTrace());
        $output .= $this->getValue($value);
        $output .= $this->theme->afterPrint();

        echo $output;
    }

    /**
     * Gets the output for any value
     * @param mixed $value Value to get the output for
     * @param boolean $showType Flag to see if the type of value should be
     * showed
     * @param boolean $encode Flag to see if the necessairy output encoding
     * should be done, set to false when a value is formatted twice by the theme
     * @return string Output of the value
     */
    private function getValue($value, $showType = true, $encode = true) {
        $type = gettype($value);

        switch($type) {
            case 'boolean':
                return $this->theme->formatValue('boolean', $value ? 'true' : 'false', null, $showType, $encode);
            case 'NULL':
                return $this->theme->formatValue(null, 'null', null, $showType, $encode);
            case 'integer':
            case 'double':
            case 'resource':
                return $this->theme->formatValue($type, (string) $value, null, $showType, $encode);
            case 'string':
                return $this->getStringValue($value, $showType, $encode);
            case 'array':
                return $this->getArrayValue($value, $showType, $encode);
            case 'object':
                return $this->getObjectValue($value);
            default:
                return $this->theme->formatValue('unknown', '???', null, $showType, $encode);
        }
    }

    /**
     * Gets the output for a string value
     * @param string $string String value to get the output for
     * @param boolean $showType Flag to see if the type of value should be
     * showed
     * @param boolean $encode Flag to see if the necessairy output encoding
     * should be done, set to false when a value is formatted twice by the theme
     * @return string Output of the string
     */
    private function getStringValue($string, $showType, $encode) {
        $length = strlen($string);
        $string = str_replace($this->stringSearch, $this->stringReplace, $string);

        $short = substr($string, 0, $this->stringLength);
        if ($length > $this->stringLength) {
            $short .= '...';
        }

        $short = '"' . $short . '"';
        $string = '"' . $string . '"';

        if ($string != $short) {
            $full = $this->theme->formatValue(null, $string, null, false, true);
        } else {
            $full = null;
        }

        return $this->theme->formatValue('string(' . $length . ')', $short, $full, $showType, $encode);
    }

    /**
     * Gets the output for an array value
     * @param array $array Array value to get the output for
     * @param boolean $showType Flag to see if the type of value should be
     * showed
     * @param boolean $encode Flag to see if the necessairy output encoding
     * should be done, set to false when a value is formatted twice by the theme
     * @return string Output of the array
     */
    private function getArrayValue($array, $showType, $encode) {
        $numItems = count($array);
        if ($numItems == 0) {
            // empty array
            return $this->theme->formatValue('array(0)', '[]', null, $showType, $encode);
        } elseif ($this->recursiveDepth == $this->recursiveMaximum) {
            // too deep in recursiveness
            return $this->theme->formatValue('array(' . $numItems . ')', '[...]', null, $showType, $encode);
        }

        // retrieve array dump
        $this->recursiveDepth++;

        $items = array();
        foreach ($array as $key => $value) {
            $items[$key] = $this->theme->formatListItem($this->getValue($key), $this->getValue($value));
        }

        $this->recursiveDepth--;

        return $this->theme->formatValue('array(' . $numItems . ')', '[...]', $this->theme->formatListItems($items), $showType);
    }

    /**
     * Gets the output for an object instance
     * @param mixed $object Object instance to get the output for
     * @return string Output of the object
     */
    private function getObjectValue($object) {
        $className = get_class($object);

        $id = array_search($object, $this->objects, true);
        if ($id !== false) {
            // already retrieved this instance
            return $this->theme->formatValue($className . '#' . $id, '{...}', null);
        } elseif ($this->recursiveDepth == $this->recursiveMaximum) {
            // too deep in recursiveness
            $this->objectId++;

            return $this->theme->formatValue($className . '#' . $this->objectId, '{...}', null);
        }

        // retrieve object instance dump
        $id = $this->objectId++;
        $this->recursiveDepth++;

        $items = array();

        // instance properties
        $properties = (array) $object;
        foreach ($properties as $property => $value) {
            $name = str_replace("\0", ':', trim($property));
            if (strpos($name, ':')) {
                list($type, $name) = explode(':', $name);
            }

            $name = '$' . $name;

            $items[$name] = $this->theme->formatListItem($this->getValue($name, false), $this->getValue($value, false));
        }

        // instance methods
        if ($this->includeMethods) {
            $class = new ReflectionClass($className);
            $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $items[] = $this->theme->formatListItem($this->getValue($this->getMethodSignature($method), false), null);
            }
        }

        $this->recursiveDepth--;

        $this->objects[$id] = $object;

        ksort($items);

        return $this->theme->formatValue($className . '#' . $id, '{...}', $this->theme->formatListItems($items));
    }


    /**
     * Gets the signature of a method
     * @param ReflectionMethod $method
     * @return string
     */
    private function getMethodSignature(ReflectionMethod $method) {
        $parameters = $method->getParameters();
        foreach ($parameters as $index => $parameter) {
            $value = '';

            if ($parameter->hasType()) {
                $value .= $parameter->getType() . ' ';
            }

            $value .= '$' . $parameter->getName();

            if ($parameter->isOptional()) {
                try {
                    $defaultValue = $this->getValue($parameter->getDefaultValue(), false, false);
                    $value .= ' = ' . $defaultValue;
                } catch (ReflectionException $e) {
                    // ignore if not retrievable
                }
            }

            $parameters[$index] = $value;
        }

        return $method->getName() . '(' . implode(', ', $parameters) . ')';
    }

    /**
     * Gets the trace of the call before this class
     * @return string File name and line number
     */
    private function getTrace() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        do {
            $caller = array_shift($backtrace);
            if (isset($caller['file']) && $caller['file'] !== __FILE__) {
                break;
            }
        } while ($caller);

        if (!$caller) {
            return null;
        }

        return $caller['file'] . ':' . $caller['line'];
    }

}

/**
 * Interface for a theme of the dump output
 */
interface VarDumpTheme {

    /**
     * Hook to generate output before the first print
     * @return string Output before the first print
     */
    public function beforeFirstPrint();

    /**
     * Hook to generate output before the print
     * @param string $trace File and linenumber where the output is generated
     * @return string Output before the print
     */
    public function beforePrint($trace);

    /**
     * Hook to generate output after the print
     * @return string Output after the print
     */
    public function afterPrint();

    /**
     * Formats a single value
     * @param string $type Type of the value
     * @param string $short Short display of the value, a preview or teaser
     * @param string $full Full display of the value
     * @param boolean $showType Flag to see if the type should be displayed
     * @param boolean $encode Encode for the output eg htmlentities
     * @return string Output of the value
     */
    public function formatValue($type, $short, $full = null, $showType = true, $encode = true);

    /**
     * Formats a single list item
     * @param string $key Formatted display of the key
     * @param string $value Formatted display of the value
     * @return string Output of the list item
     */
    public function formatListItem($key, $value = null);

    /**
     * Formats a list from items
     * @param array $items Items formatted by formatListItem
     * @return string Output of the list
     * @see formatListItem
     */
    public function formatListItems(array $items);

}

/**
 * CLI dump theme
 */
 class CliVarDumpTheme implements VarDumpTheme {

    /**
     * Hook to generate output before the first print
     * @return string Output before the first print
     */
    public function beforeFirstPrint() {
        return null;
    }

    /**
     * Hook to generate output before the print
     * @return string Output before the print
     */
    public function beforePrint($trace) {
        return "\n[" . $trace . "]\n";
    }

    /**
     * Hook to generate output after the print
     * @return string Output after the print
     */
    public function afterPrint() {
        return "\n";
    }

    /**
     * Formats a single value
     * @param string $type Type of the value
     * @param string $short Short display of the value, a preview or teaser
     * @param string $full Full display of the value
     * @param boolean $showType Flag to see if the type should be displayed
     * @param boolean $encode Encode for the output eg htmlentities
     * @return string Output of the value
     */
    public function formatValue($type, $short, $full = null, $showType = true, $encode = true) {
        $output = '';

        if ($showType && $type) {
            $output .= $type . ' ';
        }

        if ($full && $full !== $short) {
            $output .= $short;
            $lines = explode("\n", (string) $full);
            foreach ($lines as $line) {
                $output .= "\n    " . $line;
            }
        } else {
            $output .= (string) $short;
        }

        return $output;
    }

    /**
     * Formats a single list item
     * @param string $key Formatted display of the key
     * @param string $value Formatted display of the value
     * @return string Output of the list item
     */
    public function formatListItem($key, $value = null) {
        return '- ' . $key . ($value !== null ? ' => ' . $value : '');
    }

    /**
     * Formats a list from items
     * @param array $items Items formatted by formatListItem
     * @return string Output of the list
     * @see formatListItem
     */
    public function formatListItems(array $items) {
        return implode("\n", $items);
    }

}

/**
 * HTML dump theme
 */
class HtmlVarDumpTheme implements VarDumpTheme {

    /**
     * Color definitions
     * @var array
     */
    protected $colors;

    /**
     * Style definitions
     * @var array
     */
    protected $styles;

    /**
     * Id of the print call
     * @var integer
     */
    private static $printId = 1;

    /**
     * Id of the curent element
     * @var integer
     */
    private static $elementId = 1;

    /**
     * Constructs a new HTML theme
     * @return null
     */
    public function __construct() {
        if (!$this->colors) {
            $this->colors = array(
                'general-background' => 'whitesmoke',
                'general-text' => 'black',
                'general-link' => 'black',
                'general-border' => 'black',
                'code-background' => 'white',
                'code-text' => 'red',
            );
        }

        $this->styles = array(
            'container' => 'font-family: monospace; padding: 1em; margin: 1em; line-height: 1.5em; border-radius: 5px; border: 1px solid ' . $this->colors['general-border'] . '; color: ' . $this->colors['general-text'] . '; background-color: ' . $this->colors['general-background'],
            'trace' => 'font-size: 0.8em',
            'link' => 'font-size: 0.8em; color: '. $this->colors['general-link'],
            'code' => 'background-color: ' . $this->colors['code-background'] . '; color: ' . $this->colors['code-text'],
            'list' => 'list-style: none; margin: 0; padding: 0 0 0 1.5em',
            'list-item' => 'margin: 0',
            'expand-string' => 'margin-left: 1.5em',
            'expand-block' => 'display: none',
        );
    }

    /**
     * Hook to generate output before the first print
     * @return string Output before the first print
     */
    public function beforeFirstPrint() {
        return '<script>
            function gotoVardump(id) {
                expandAllVardump(' . self::$printId . ');

                var url = "" + window.location;

                window.location = url.replace(/#[A-Za-z0-9_-]*$/, "") + "#vardump-anchor-" + id;

                history.replaceState(null, null, url);

                return false;
            }

            function expandAllVardump(id) {
                document.body.style.cursor = "progress";

                var blocks = document.getElementsByClassName("vardump-block-" + id);
                for (var i = 0, l = blocks.length; i < l; i++) {
                    blocks[i].style.display = "block";
                }

                var links = document.getElementsByClassName("vardump-link-" + id);
                for (var i = 0, l = links.length; i < l; i++) {
                    links[i].innerHTML = "[reduce]";
                }

                document.body.style.cursor = "default";

                return false;
            }

            function reduceAllVardump(id) {
                document.body.style.cursor = "wait";

                var blocks = document.getElementsByClassName("vardump-block-" + id);
                for (var i = 0, l = blocks.length; i < l; i++) {
                    blocks[i].style.display = "none";
                }

                var links = document.getElementsByClassName("vardump-link-" + id);
                for (var i = 0, l = links.length; i < l; i++) {
                    links[i].innerHTML = "[expand]";
                }

                document.body.style.cursor = "auto";

                return false;
            }

            function toggleVardump(id) {
                var block = document.getElementById("vardump-block-" + id);
                var link = document.getElementById("vardump-link-" + id);

                if (block.style.display == "block" ) {
                    block.style.display = "none";
                    link.innerHTML = "[expand]";
                } else {
                    block.style.display = "block";
                    link.innerHTML = "[reduce]";
                }

                return false;
            }
        </script>';
    }

    /**
     * Hook to generate output before the print
     * @return string Output before the print
     */
    public function beforePrint($trace) {
        $output = '<div style="' . $this->styles['container'] . '">';
        $output .= '<div style="' . $this->styles['trace'] . '">' . htmlentities($trace) . '</div>' . "\n";
        $output .= '<div>';
        $output .= '<a style="' . $this->styles['link'] . '" href="#" onclick="return expandAllVardump(' . self::$printId . ');">[expand all]</a>';
        $output .= ' ';
        $output .= '<a style="' . $this->styles['link'] . '" href="#" onclick="return reduceAllVardump(' . self::$printId . ');">[reduce all]</a>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Hook to generate output after the print
     * @return string Output after the print
     */
    public function afterPrint() {
        self::$printId++;

        return '</div>';
    }

    /**
     * Formats a single value
     * @param string $type Type of the value
     * @param string $short Short display of the value, a preview or teaser
     * @param string $full Full display of the value
     * @param boolean $showType Flag to see if the type should be displayed
     * @param boolean $encode Encode for the output eg htmlentities
     * @return string Output of the value
     */
    public function formatValue($type, $short, $full = null, $showType = true, $encode = true) {
        $output = '';

        if ($showType && $type) {
            $output .= $type . ' ';
        }

        if ($encode) {
            $output .= '<code style="' . $this->styles['code'] . '">';
            $output .= htmlentities((string) $short);
            $output .= '</code>';
        } else {
            $output .= (string) $short;
        }

        if (strpos($type, '#')) {
            list($className, $id) = explode('#', $type);
        } else {
            $id = null;
        }

        if ($full && $full !== $short) {
            self::$elementId++;

            $this->anchors[$id] = self::$elementId;

            $output .= ' <a style="' . $this->styles['link'] . '" href="#" id="vardump-link-' . self::$elementId . '" class="vardump-link-' . self::$printId . '" onclick="return toggleVardump(' . self::$elementId . ');">[expand]</a> ';
            $output .= '<a name="vardump-anchor-' . self::$elementId . '"></a>';
            $output .= '<div style="' . $this->styles['expand-block'] . '" id="vardump-block-' . self::$elementId . '" class="vardump-block-' . self::$printId . '">';
            if (substr($full, 0, 3) == '<ul') {
                $output .= $full;
            } else {
                $output .= '<div style="' . $this->styles['expand-string'] . '">' . $full . '</div>';
            }
            $output .= '</div>';
        } elseif ($id && isset($this->anchors[$id])) {
            $output .= ' <a style="' . $this->styles['link'] . '" href="#" onclick="return gotoVardump(' . $this->anchors[$id] . ');">[goto]</a> ';
        }

        return $output;
    }

    /**
     * Formats a single list item
     * @param string $key Formatted display of the key
     * @param string $value Formatted display of the value
     * @return string Output of the list item
     */
    public function formatListItem($key, $value = null) {
        return '<li class="' . $this->styles['list-item'] . '">' . $key . ($value !== null ? ' => ' . $value : '') . '</li>';
    }

    /**
     * Formats a list from items
     * @param array $items Items formatted by formatListItem
     * @return string Output of the list
     * @see formatListItem
     */
    public function formatListItems(array $items) {
        return '<ul style="' . $this->styles['list'] . '">' . implode('', $items) . '</ul>';
    }

}

/**
 * Batman HTML dump theme
 */
class BatmanHtmlVarDumpTheme extends HtmlVarDumpTheme {

    /**
     * Constructs a new HTML theme
     * @return null
     */
    public function __construct() {
        $this->colors = array(
            'general-background' => 'black',
            'general-text' => 'gold',
            'general-link' => 'lightgray',
            'general-border' => 'black',
            'code-background' => 'black',
            'code-text' => 'yellow',
        );

        parent::__construct();
    }

}

/**
 * Hulk HTML dump theme
 */
 class HulkHtmlVarDumpTheme extends HtmlVarDumpTheme {

    /**
     * Constructs a new HTML theme
     * @return null
     */
    public function __construct() {
        $this->colors = array(
            'general-background' => 'honeydew',
            'general-text' => 'green',
            'general-link' => 'darkgreen',
            'general-border' => 'green',
            'code-background' => 'white',
            'code-text' => 'purple',
        );

        parent::__construct();
    }

}

/**
 * Ironman HTML dump theme
 */
 class IronmanHtmlVarDumpTheme extends HtmlVarDumpTheme {

    /**
     * Constructs a new HTML theme
     * @return null
     */
    public function __construct() {
        $this->colors = array(
            'general-background' => 'snow',
            'general-text' => 'red',
            'general-link' => 'darkred',
            'general-border' => 'red',
            'code-background' => 'ivory',
            'code-text' => 'darkred',
        );

        parent::__construct();
    }

}

/**
 * Spiderman HTML dump theme
 */
 class SpidermanHtmlVarDumpTheme extends HtmlVarDumpTheme {

    /**
     * Constructs a new HTML theme
     * @return null
     */
    public function __construct() {
        $this->colors = array(
            'general-background' => 'aliceblue',
            'general-text' => 'blue',
            'general-link' => 'darkblue',
            'general-border' => 'blue',
            'code-background' => 'white',
            'code-text' => 'red',
        );

        parent::__construct();
    }

}

/**
 * Superman HTML dump theme
 */
 class SupermanHtmlVarDumpTheme extends HtmlVarDumpTheme {

    /**
     * Constructs a new HTML theme
     * @return null
     */
    public function __construct() {
        $this->colors = array(
            'general-background' => 'aliceblue',
            'general-text' => 'blue',
            'general-link' => 'blue',
            'general-border' => 'blue',
            'code-background' => 'LightYellow',
            'code-text' => 'red',
        );

        parent::__construct();
    }

}
