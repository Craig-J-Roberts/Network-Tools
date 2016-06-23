<?php

namespace cjr\NetworkTools\Exception;

/**
 * DHCP Client is not running
 *
 * @author craig
 */
class DHCPNotRunningException extends \Exception
{
    public function __construct($interface, $code = 11, Exception $previous = null)
    {
        $message = 'DHCP is not running on '.$interface;
        parent::__construct($message, $code, $previous);
    }
}
