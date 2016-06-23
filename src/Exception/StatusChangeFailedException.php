<?php

namespace cjr\NetworkTools\Exception;

/**
 * The interface did not change it's status when requested
 *
 * @author Craig Roberts
 */
class StatusChangeFailedException extends \Exception
{
    public function __construct($status, $code = 18, Exception $previous = null)
    {
        $message = 'Failed to change status to '. $status;
        parent::__construct($message, $code, $previous);
    }
}
