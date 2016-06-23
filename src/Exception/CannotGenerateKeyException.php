<?php

namespace cjr\NetworkTools\Exception;

/**
 * Generation of WPA/WPA2 Key has failed
 *
 * @author Craig Roberts
 */
class CannotGenerateKeyException extends \Exception
{
    public function __construct($interface, $code = 10, Exception $previous = null)
    {
        $message = 'Could not generate WPA / WPA2 Key for: '.$interface;
        parent::__construct($message, $code, $previous);
    }
}
