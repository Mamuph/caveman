<?php

/**
 * Replacer helper
 * (A minimalist moustache dialect)
 */
class Replacer
{

    /**
     * Replace keytags by array values
     *
     * @param array     $ref
     * @param string    $string
     * @param string    $prefix
     * @return string
     */
    public static function replace_from_array(array $ref, $string, $prefix = '')
    {
        foreach ($ref as $k => $item)
            $string = str_replace('#{{'. $prefix . '.' . strtoupper($k) . '}}', $item, $string);

        return $string;

    }

}