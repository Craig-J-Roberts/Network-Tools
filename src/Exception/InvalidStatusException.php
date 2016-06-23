<?php

namespace cjr\NetworkTools\Exception;

/**
 * The status given is not valid
 *
 * @author Craig Roberts
 */
class InvalidStatusException extends \Exception
{
    public function __construct($status, $code = 13, Exception $previous = null)
    {
        $message = $status .' is not a valid status';
        parent::__construct($message, $code, $previous);
    }
}
