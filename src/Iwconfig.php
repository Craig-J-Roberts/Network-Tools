<?php

namespace cjr\NetworkTools;

use cjr\NetworkTools\Exception\InvalidInterfaceException;
use cjr\NetworkTools\Exception\WPASupplicantStillRunningException;
use cjr\NetworkTools\Exception\WPASupplicantNotRunningException;
use cjr\NetworkTools\Exception\InvalidEncryptionTypeException;
use cjr\NetworkTools\WPASupplicant;
use cjr\NetworkTools\Iwlist;

/**
 * Representation of iwconfig console command
 *
 * @author Craig Roberts
 */
class Iwconfig
{
    const ENC_NONE = 0;
    const ENC_WEP = 1;
    const ENC_WPA = 2;
    const ENC_WPA2 = 3;
    const ENC_UNKNOWN = 4;

    private $interfaces = [];
    private $dataLoaded = false;
    /**
     * Connect to specified wireless network
     * @param string $network SSID of network to connect to
     * @param string $interface Interface to connect on
     * @param string $key Encryption key for network
     * @param int $encType Type of encryption in use on network
     * @return boolean true if connection successful, false if not
     */
    public function connect($network, $interface, $key = null, $encType = null)
    {
        $this->interface = $interface;
        $this->network = $network;

        if (is_null($key)) {
            $encType = self::ENC_NONE;
        } elseif (is_null($encType)) {
            $encType = $this->getEncryptionType();
        }

        $encCommand = $this->setupEncryption($key, $encType);

        $cmd = ' essid ' . escapeshellarg($network) . ' ' . $encCommand;
        $this->execute($cmd, $interface);

        return $this->isAssociatedTimer($interface);
    }
    /**
     * Disconnect from wireless network
     * @param type $interface Wireless network interface
     * @return boolean true if success, false on failiure
     * @throws WPASupplicantStillRunningException
     */
    public function disconnect($interface)
    {
        $WPASupplicant = new WPASupplicant($interface);
        if ($WPASupplicant->isRunning()) {
            if (!$WPASupplicant->stop()) {
                throw new WPASupplicantStillRunningException($interface);
            }
        }
        return true;
    }
    /**
     * Indicates whether given interface is associated with a network
     * @param string $interface Wireless network interface
     * @return boolean true if associated with network, false if not
     * @throws InvalidInterfaceException
     */
    public function isAssociated($interface)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        return array_key_exists('ssid', $this->interfaces[$interface]);
    }
    /**
     * Returns a list of wireless network interfaces
     * @return array List of wireless network interfaces
     */
    public function getInterfaceList()
    {
        $this->getData();
        return array_keys($this->interfaces);
    }
    /**
     * Indicates whether a given interface has wireless extensions
     * @param string $interface Interface to test
     * @return boolean true if interface is valud, false if not
     */
    public function validInterface($interface)
    {
        return in_array($interface, $this->getInterfaceList());
    }
    /**
     * Forces a refresh of cached data
     */
    public function forceRefresh()
    {
        $this->dataLoaded = false;
        $this->interfaces = [];
    }
    /**
     * Indicates whether given interface is associated with wireless network, allows up to 10sec to associate
     * @param string $interface Wireless Network interface
     * @return boolean true if associated, false if not
     */
    private function isAssociatedTimer($interface)
    {
        $startTime = microtime(true);
        $timeDifference = 0;
        while ($timeDifference < 10) {
            $this->forceRefresh();
            if (!$this->isAssociated($interface)) {
                return true;
            }
            $timeDifference = microtime(true) - $startTime;
        }
        return $this->isAssociated();
    }
    /**
     * Tries to work out type of encryption in use on network
     * @return int Encryption Type Constant
     */
    private function getEncryptionType()
    {
        $iwlist = new Iwlist($this->interface);
        $encActive = $iwlist->isEncryptionActive($this->network);
        $encTypes = $iwlist->getEncryptionTypes($this->network);
        if (!$encActive) {
            return self::ENC_NONE;
        } elseif (in_array('IEEE 802.11i/WPA2 Version 1', $encTypes)) {
            return self::ENC_WPA2;
        } elseif (in_array('WPA Version 1', $encTypes)) {
            return self::ENC_WPA;
        } elseif ($encActive && empty($encTypes)) {
            return self::ENC_WEP;
        } else {
            return self::ENC_UNKNOWN;
        }
    }
    /**
     * Configures the encryption for the connection
     * @param string $key Encryption Key
     * @param int $encType Class Constant of the type of encryption to use
     * @return string Command line to issue to iwconfig
     * @throws WPASupplicantNotRunningException
     * @throws InvalidEncryptionTypeException
     */
    private function setupEncryption($key, $encType)
    {
        switch ($encType) {
            case self::ENC_NONE:
                return '';
            case self::ENC_WEP:
                return  'key '. escapeshellarg($key);
            case self::ENC_WPA:
                //Same as WPA2;
            case self::ENC_WPA2:
                if (!$this->setupWPAKey($key)) {
                    throw new WPASupplicantNotRunningException($this->interface);
                }
                return '';
            default:
                throw new InvalidEncryptionTypeException($encType);
        }
    }
    /**
     * Sets up a WPA/WPA2 Encryption key
     * @param string $key Encryption Key
     * @return boolean true if successful, false if not
     */
    private function setupWPAKey($key)
    {
        $wpaSupplicant = new WPASupplicant($this->interface);
        $wpaSupplicant->setSSID($this->network);
        $wpaSupplicant->setKey($key);
        return $wpaSupplicant->start();
    }
    /**
     * Executes a command on iwconfig
     * @param string $cmd Command to execute
     * @param string $interface Interface to apply command to
     * @throws InvalidInterfaceException
     */
    private function execute($cmd, $interface)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        exec('iwconfig ' . escapeshellarg($interface) . ' ' . $cmd);
    }
    /**
     * Checks to see if data is loaded and loads if required
     */
    private function getData()
    {
        if (!$this->dataLoaded) {
            $this->refreshData();
            $this->dataLoaded = true;
        }
    }
    /**
     * Refreshes data from iwconfig command
     */
    private function refreshData()
    {
        $output = [];
        exec('iwconfig 2> /dev/null', $output);
        foreach ($output as $line) {
            $firstChar = substr($line, 0, 1);
            if ($firstChar != " " && $firstChar != "") {
                $interface = rtrim(substr($line, 0, 9));
                $this->interfaces[$interface] = [];
            }
            if (preg_match('/(?<=ESSID:")[\S\s]+(?=")/', $line, $matches)) {
                $this->interfaces[$interface]['ssid'] = $matches[0];
            }
        }
    }
}
