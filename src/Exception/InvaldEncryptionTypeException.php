<?php

namespace cjr\NetworkTools\Exception;

/**
 * Encryption type is unknown or not supported
 *
 * @author Craig Roberts
 */
class InvaldEncryptionTypeException extends \Exception
{
    public function __construct($encType, $code = 13, Exception $previous = null)
    {
        $message = $encType .' is not a valid encryption type';
        parent::__construct($message, $code, $previous);
    }
}
