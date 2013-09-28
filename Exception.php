<?php //-->
/*
 * This file is part of the Mysql package of the Eden PHP Library.
 * (c) 2013-2014 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE
 * distributed with this package.
 */

namespace Eden\Jabber;

use Eden\Core\Exception as CoreException;

/**
 * Sql Errors
 *
 * @vendor Eden
 * @package Mysql
 * @author Aldrin Bautista adinbautista@gmail.com
 */
class Exception extends CoreException
{
    const CONNECTION_FAILED = 'Connection to %s on port %s failed';
    const NO_FEATURES = 'Error: No feature information from server available';
    const NOT_CONNECTED = 'Not connected';
    const NO_JID = 'No jid given.';
    const NO_AUTH_METHOD = 'No authentication method supported';
    const NO_SASL = 'Server does not offer SASL authentication';
    const SERVER_FAILED = 'Server sent a failiure message';
    const TLS_CHANGE_FAILED = 'TLS mode change failed';
    public static function i($message = null, $code = 0)
    {
        $class = __CLASS__;
        return new $class($message, $code);
    }
}
