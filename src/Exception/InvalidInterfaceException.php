<?php

namespace cjr\NetworkTools\Exception;

/**
 * The network interface specified is invalid
 *
 * @author Craig Roberts
 */
class InvalidInterfaceException extends \Exception
{
    public function __construct($interface, $code = 15, Exception $previous = null)
    {
        $message = $interface .' is not a valid interface';
        parent::__construct($message, $code, $previous);
    }
}
