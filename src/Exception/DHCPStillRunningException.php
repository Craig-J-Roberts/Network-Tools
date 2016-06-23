<?php

namespace cjr\NetworkTools\Exception;

/**
 * DHCP Client is still running after stop request
 *
 * @author Craig Roberts
 */
class DHCPStillRunningException extends \Exception
{
    public function __construct($interface, $code = 12, Exception $previous = null)
    {
        $message = 'DHCP is still running on '.$interface;
        parent::__construct($message, $code, $previous);
    }
}
