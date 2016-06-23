<?php

namespace cjr\NetworkTools;

use cjr\NetworkTools\Ifconfig;
use cjr\NetworkTools\Exception\InvalidInterfaceException;
use cjr\NetworkTools\Exception\DHCPNotRunningException;
use cjr\NetworkTools\Exception\DHCPStillRunningException;

/**
 * Representation of the busybox udhcpc console command
 *
 * @author Craig Roberts
 */
class DHCPClient
{
    /**
     * Checks given interface is valid
     * @param string $interface Network interface to control DHCP process on
     * @throws InvalidInterfaceException
     */
    public function __construct($interface)
    {
        $ifconfig = new Ifconfig;
        if (!$ifconfig->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        $this->interface = $interface;
        $this->pidFile = '/var/run/DHCP_' . $interface . '.pid';
    }
    /**
     * Starts DHCP process
     * @return boolean true if DHCP process has started, false if not
     */
    public function start()
    {
        if ($this->isRunning()) {
            if (!$this->stop()) {
                throw new DHCPStillRunningException($this->interface);
            }
        }
        exec('/sbin/udhcpc -b -t 1 -i ' . escapeshellarg($this->interface) . ' -p '. escapeshellarg($this->pidFile));
        return $this->isRunning();
    }
    /**
     * Stops DHCP process
     * @return boolean true if DHCP process has been stopped
     */
    public function stop()
    {
        $pid = $this->getPID();
        exec('kill -SIGTERM ' . escapeshellarg((int)$pid));
        if ($this->isRunningTimer()) {
            return true;
        }

        return $this->terminate();
    }
    /**
     * Terminates DHCP process
     * @return boolean true of DHCP process has terminated
     */
    public function terminate()
    {
        $pid = $this->getPID();
        exec('kill -SIGKILL ' . escapeshellarg((int)$pid));
        return $this->isRunningTimer();
    }
    /**
     * Checks if DHCP process is running
     * @return boolean true if DHCP process is running, false if not
     */
    public function isRunning()
    {
        return file_exists($this->pidFile);
    }
    /**
     * Gives the DHCP process 1 second to start
     * @return boolean true if DHCP process is running within 1 second, false if not
     */
    private function isRunningTimer()
    {
        $startTime = microtime(true);
        $timeDifference = 0;
        while ($timeDifference < 1) {
            if ($this->isRunning()) {
                return true;
            }
            $timeDifference = microtime(true) - $startTime;
        }
        return $this->isRunning();
    }
    /**
     * Gets the PID of the DHCP process
     * @return int PID of DHCP Process
     * @throws DHCPNotRunningException
     */
    private function getPID()
    {
        if (!$this->isRunning()) {
            throw new DHCPNotRunningException($this->interface);
        }
        $pidFile = @fopen($this->pidFile, 'r');
        $pid = fread($pidFile, filesize($this->pidFile));
        fclose($pidFile);
        return (int)$pid;
    }
}
