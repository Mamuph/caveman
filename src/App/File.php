<?php

/**
 * File helper extension.
 *
 * @package    Caveman
 * @category   Helpers
 * @author     Mamuph
 * @copyright  (c) 2007-2018 Mamuph Team
 */
class File extends Core_File
{

    /**
     * Match filename path and filesystem path.
     *
     * @example
     *
     *      ::matchFilenamePattern('/etc/php/php.ini', '/etc/php/php.*');      // True
     *      ::matchFilenamePattern('/etc/php/php.ini', '/etc/php/*.ini');      // True
     *      ::matchFilenamePattern('/etc/php/php.ini', '/etc/*hp/*.ini');      // True
     *      ::matchFilenamePattern('/etc/php/php.ini', '/etc/php/config.ini'); // False
     *      ::matchFilenamePattern('/etc/php/php.ini', '/etc/php/config.*');   // False
     *
     * @param $filename
     * @param $pattern
     * @return bool
     */
    public static function matchFilenamePattern($filename, $pattern) : bool
    {
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = str_replace('.', '\.', $pattern);
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '/' . $pattern . '$/';

        return (bool) preg_match($pattern, $filename);
    }

}