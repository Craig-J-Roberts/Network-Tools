<?php

namespace cjr\NetworkTools;

use cjr\NetworkTools\Exception\InvalidInterfaceException;
use cjr\NetworkTools\Exception\InvalidSSIDException;

/**
 *  Representation of the iwlist console command
 *
 * @author Craig Roberts
 */
class Iwlist
{
    private $networks = [];
    private $dataLoaded = false;
    /**
     * Constructor Class
     * @param string $interface Wireless Interface
     * @throws InvalidInterfaceException
     */
    public function __construct($interface)
    {
        $iwconfig = new Iwconfig;
        if (!$iwconfig->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }
        $this->interface = $interface;
    }
    /**
     * Lists encryption types available for specified network
     * @param string $network SSID of Network
     * @return array List of encryption types
     * @throws InvalidSSIDException
     */
    public function getEncryptionTypes($network)
    {
        if (!$this->validNetwork($network)) {
            throw new InvalidSSIDException($network);
        }

        if (!array_key_exists('encryption', $this->networks[$network])) {
            $this->networks[$network]['encryption'] = [];
        }
        return $this->networks[$network]['encryption'];
    }
    /**
     * Indicates whether encryption is active on specified network
     * @param string $network SSID of Network
     * @return boolean true if encryption is active, false if not
     * @throws InvalidSSIDException
     */
    public function isEncryptionActive($network)
    {
        if (!$this->validNetwork($network)) {
            throw new InvalidSSIDException($network);
        }

        return array_key_exists('encryptionActive', $this->networks[$network]);
    }
    /**
     * Returns an array of wireless networks
     * @return array List of networks & details
     */
    public function getNetworkList()
    {
        $this->getData();
        return $this->networks;
    }
    /**
     * Indicates whether given network is visible
     * @param string $network SSID of network to test
     * @return boolean true if network is visible, false if not
     */
    public function validNetwork($network)
    {
        return in_array($network, array_keys($this->getNetworkList()));
    }
    /**
     * Forces a refresh of cached data
     */
    public function forceRefresh()
    {
        $this->dataLoaded = false;
        $this->networks = [];
    }
    /**
     * Retrieves data from command if not already loaded
     */
    private function getData()
    {
        if (!$this->dataLoaded) {
            $this->refreshData();
            $this->dataLoaded = true;
        }
    }
    /**
     * Refreshes data from command and populates array
     */
    private function refreshData()
    {
        exec('iwlist ' . escapeshellarg($this->interface) .' scan 2> /dev/null', $output);
        $cell = 0;
        $ssidOnNextLine = false;
        foreach ($output as $line) {
            $isCell = substr($line, 10, 4);
            if ($ssidOnNextLine) {
                preg_match('/(?<=ESSID:")[\S\s]+(?=")/', $line, $matches);
                $ssid = $matches[0];
                $ssidOnNextLine = false;
            } elseif ($isCell == 'Cell') {
                $cell++;
                $ssidOnNextLine = true;
            }

            if (preg_match('/(?<=IE: )[\S\s]+/', $line, $matches)) {
                $this->networks[$ssid]['encryption'][] = $matches[0];
            }

            if (preg_match('/(?<=Encryption key:)on/', $line, $matches)) {
                $this->networks[$ssid]['encryptionActive'] = true;
            }

            if (preg_match('/(?<=Signal level=)[0-9]+/', $line, $matches)) {
                if (@$this->networks[$ssid]['signalStrength'] < $matches[0]) {
                    $this->networks[$ssid]['signalStrength'] = $matches[0];
                }
            }
        }
    }
}
