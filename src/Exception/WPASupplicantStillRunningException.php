<?php

namespace cjr\NetworkTools\Exception;

/**
 * WPA Supplicant is still running after a stop request
 *
 * @author Craig Roberts
 */
class WPASupplicantStillRunningException extends \Exception
{
    public function __construct($interface, $code = 20, Exception $previous = null)
    {
        $message = 'WPA Supplicant is still running on '.$interface;
        parent::__construct($message, $code, $previous);
    }
}
