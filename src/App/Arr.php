<?php


/**
 * Array helper extension.
 *
 * @package    Caveman
 * @category   Helpers
 * @author     Mamuph
 * @copyright  (c) 2007-2016 Mamuph Team
 */
class Arr extends Core_Arr
{


    /**
     * Array friendly version of preg_match
     *
     * @param array $patterns
     * @param $subject
     * @param array|null $matches
     * @param int $flag
     * @param int $offset
     * @return bool|int
     */
    public static function preg_match(array $patterns, $subject, array &$matches = null, $flag = 0, $offset = 0)
    {
        foreach ($patterns as $pattern)
        {
            $found = preg_match($pattern, $subject, $matches, $flag, $offset);

            if ($found === 1)
                return 1;
            else if ($found === false)
                return false;
        }

        return 0;
    }

}
