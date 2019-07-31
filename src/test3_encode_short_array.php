<?php

$array = array (
  'chiron/php-renderer' => 
  array (
    'providers' => 
    array (
      0 => 'Chiron\\Views\\Provider\\PhpRendererServiceProvider',
    ),
  ),
);


$export = Exporter::export($array);

die($export);


class Exporter
{
    private static $output;

    public static function export($var): string
    {
        self::$output = '';
        self::exportInternal($var, 0);
        return self::$output;
    }
    /**
     * @param mixed $var variable to be exported
     * @param int $level depth level
     */
    private static function exportInternal($var, int $level): void
    {
        switch (gettype($var)) {
            case 'NULL':
                self::$output .= 'null';
                break;
            case 'array':
                if (empty($var)) {
                    self::$output .= '[]';
                } else {
                    $keys = array_keys($var);
                    $outputKeys = ($keys !== range(0, count($var) - 1));
                    $spaces = str_repeat(' ', $level * 4);
                    self::$output .= '[';
                    foreach ($keys as $key) {
                        self::$output .= "\n" . $spaces . '    ';
                        if ($outputKeys) {
                            self::exportInternal($key, 0);
                            self::$output .= ' => ';
                        }
                        self::exportInternal($var[$key], $level + 1);
                        self::$output .= ',';
                    }
                    self::$output .= "\n" . $spaces . ']';
                }
                break;
            case 'object':
                // we do nothing because we can't have objects in the composer extra array data.
                break;
            default:
                // string / integer / boolean / double
                self::$output .= var_export($var, true);
        }
    }

}

