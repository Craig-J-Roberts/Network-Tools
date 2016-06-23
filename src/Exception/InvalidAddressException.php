<?php

namespace cjr\NetManager\Exception;

/**
 * The address specified is invalid
 *
 * @author Craig Roberts
 */
class InvalidAddressException extends \Exception
{

    public function __construct($address, $code = 14, Exception $previous = null)
    {
        $message = $address .' is not a valid address';
        parent::__construct($message, $code, $previous);
    }
}
