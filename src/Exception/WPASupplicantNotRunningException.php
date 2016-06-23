<?php

namespace cjr\NetworkTools\Exception;

/**
 * WPA Supplicant is not running
 *
 * @author Craig Roberts
 */
class WPASupplicantNotRunningException extends \Exception
{
    public function __construct($interface, $code = 19, Exception $previous = null)
    {
        $message = 'WPA Supplicant is not running on '.$interface;
        parent::__construct($message, $code, $previous);
    }
}
