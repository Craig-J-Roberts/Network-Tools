<?php

namespace cjr\NetworkTools;

use cjr\NetworkTools\Exception\InvalidInterfaceException;
use cjr\NetworkTools\Exception\WPASupplicantNotRunningException;
use cjr\NetworkTools\Exception\WPASupplicantStillRunningException;
use cjr\NetworkTools\Exception\CannotGenerateKeyException;

/**
 * Representation of the wpa_supplicant application
 *
 * @author Craig Roberts
 */
class WPASupplicant
{
    /**
     * Constructor
     * @param string $interface Network Interface
     * @throws InvalidInterfaceException
     */
    public function __construct($interface)
    {
        $iwconfig = new Iwconfig;
        if (!$iwconfig->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        $this->interface = $interface;

        $this->configFile = '/tmp/WPA_' . $interface;
        $this->pidFile = '/var/run/WPA_' . $interface . '.pid';
    }
    /**
     * Sets the encryption key for the network
     * @param string $key Encryption Key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
    /**
     * Sets the SSID of the wireless network
     * @param string $network SSID of the wireless network
     */
    public function setSSID($network)
    {
        $this->network = $network;
    }
    /**
     * Starts the wpa_supplicant application.
     * @return bool true if wpa_supplicant running, false if not
     * @throws WPASupplicantStillRunningException
     */
    public function start()
    {
        if ($this->isRunning()) {
            if (!$this->stop()) {
                throw new WPASupplicantStillRunningException($this->interface);
            }
        }

        $this->generateKey();
        exec('wpa_supplicant -B -i '. escapeshellarg($this->interface) .' -Dwext -c ' . escapeshellarg($this->configFile) . ' -P ' . escapeshellarg($this->pidFile));

        return $this->isRunningTimer();
    }
    /**
     * Stops the wpa_supplicant application.
     * @return boolean true if wpa_supplicant stopped, false if still running
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
     * Sends the SIGKILL signal to wpa_supplicant.
     * @return boolean true if wpa_supplicant terminated, false if still running
     */
    public function terminate()
    {
        $pid = $this->getPID();
        exec('kill -SIGKILL ' . escapeshellarg((int)$pid));
        return $this->isRunningTimer();
    }
    /**
     * Signals whether wpa_supplicant is running.
     * @return boolean true if wpa_supplicant is running, false if not
     */
    public function isRunning()
    {
        return file_exists($this->pidFile);
    }
    /**
     * Returns the PID of the wpa_supplicant application.
     * @return int PID of wpa_supplicant application
     * @throws WPASupplicantNotRunningException
     */
    private function getPID()
    {
        if (!$this->isRunning()) {
            throw new WPASupplicantNotRunningException($this->interface);
        }
        $pidFile = @fopen($this->pidFile, 'r');
        $pid = fread($pidFile, filesize($this->pidFile));
        fclose($pidFile);
        return (int)$pid;
    }
    /**
     * Waits up to 5 sec for wpa_supplicant to start
     * @return boolean true if wpa_supplicant running, false if not
     */
    private function isRunningTimer()
    {
        $startTime = microtime(true);
        $timeDifference = 0;
        while ($timeDifference < 5) {
            if ($this->isRunning()) {
                return true;
            }
            $timeDifference = microtime(true) - $startTime;
        }
        return $this->isRunning();
    }
    /**
     * Generates WPA/WPA2 Key & Config file for wpa_supplicant application
     * @throws CannotGenerateKeyException
     */
    private function generateKey()
    {
        $ssid = $this->network;
        $key = $this->key;

        $results = [];

        exec('wpa_passphrase '. escapeshellarg($ssid) . ' ' . escapeshellarg($key), $results);
        if (preg_match('/(?<=	psk=)\w+/', implode($results), $matches)) {
            $psk = $matches[0];
        } else {
            throw new CannotGenerateKeyException($this->interface);
        }

        $config = "network={".PHP_EOL;
        $config .= "    ssid=\"$ssid\"".PHP_EOL;
        $config .= "    psk=$psk".PHP_EOL;
        $config .= "}";

        $configFile = fopen($this->configFile, 'w+');
        fwrite($configFile, $config);
        fclose($configFile);
    }
}
