<?php

/**
 * @package    local_intelliboard
 * @copyright  2018 Intelliboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    https://intelliboard.net/
 */

namespace local_intelliboard\tools;

class os_classifier {

    const TYPE_DESKTOP = 'desktop';
    const TYPE_MOBILE = 'mobile';
    const TYPE_OTHER = 'other';

    private static $os = array(
        self::TYPE_DESKTOP => array(
            'Windows 10',
            'Windows 8.1',
            'Windows 8',
            'Windows 7',
            'Windows Vista',
            'Windows 2003',
            'Windows XP',
            'Windows 2000',
            'Windows NT 4.0',
            'Windows NT',
            'Windows 98',
            'Windows 95',
            'Windows Phone',
            'Unknown Windows OS',
            'Mac OS X',
            'Power PC Mac',
            'ppc mac',
            'freebsd',
            'ppc',
            'Macintosh',
            'linux',
            'debian',
            'sunos',
            'Sun Solaris',
            'beos',
            'GNU/Linux',
            'gnu',
            'unix',
            'Unknown Unix OS',
            'netbsd',
            'bsdi',
            'openbsd'
        ),
        self::TYPE_MOBILE => array(
            'Android',
            'IOS',
            'iphone',
            'ipad',
            'ipod',
            'BlackBerry',
            'Symbian OS',
            'symbian'
        )
    );

    public static function getOSType($required) {

        $required = strtolower($required);

        foreach (self::$os as $type => $osses) {

            foreach ($osses as $os) {

                if (strtolower($os) === $required) {
                    return $type;
                }

            }

        }

        return self::TYPE_OTHER;

    }

}