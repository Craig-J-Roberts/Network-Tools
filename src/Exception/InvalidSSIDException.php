<?php

namespace cjr\NetworkTools\Exception;

/**
 * The SSID specified cannot be found
 *
 * @author Craig Roebrts
 */
class InvalidSSIDException extends \Exception
{
    public function __construct($ssid, $code = 16, Exception $previous = null)
    {
        $message = $ssid .' is not a valid SSID';
        parent::__construct($message, $code, $previous);
    }
}
