<?php

namespace cjr\NetworkTools;

use cjr\NetworkTools\Exception\InvalidAddressException;
use cjr\NetworkTools\Exception\InvalidInterfaceException;
use cjr\NetworkTools\Exception\StatusChangeFailedException;
use cjr\NetworkTools\Exception\InvalidStatusException;

/**
 * Representation of the busybox ifconfig console command
 *
 * @author Craig Roberts
 */
class Ifconfig
{
    const STATUS_UP = 1;
    const STATUS_DOWN = 0;

    private $dataLoaded = false;
    private $interfaces = array();

    /**
     * Returns the address of given interface
     * @param string $interface Network Interface
     * @return string Returns the address of $interface or empty string if no address found
     * @throws InvalidInterfaceException
     */
    public function getAddress($interface)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        if (array_key_exists('address', $this->interfaces[$interface])) {
            return $this->interfaces[$interface]['address'];
        } else {
            return '';
        }
    }
    /**
     * Sets address of given interface
     * @param string $interface Network Interface
     * @param string $address Address to assign
     * @return boolean Returns true on success, false on failure
     * @throws InvalidAddressException
     * @throws InvalidInterfaceException
     */
    public function setAddress($interface, $address)
    {
        if (!$this->validIP($address)) {
            throw new InvalidAddressException($address);
        }

        $cmd = 'add ' . escapeshellarg($address);
        $this->execute($cmd, $interface);
        $this->forceRefresh();

        return ($this->getAddress($interface) == $address);
    }
    /**
     * Returns the HWAddress for given interface
     * @param string $interface Network Interface
     * @return string Hardware Address
     * @throws InvalidInterfaceException
     */
    public function getHWAddress($interface)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        if (array_key_exists('hwaddr', $this->interfaces[$interface])) {
            return $this->interfaces[$interface]['hwaddr'];
        } else {
            return '';
        }
    }
    /**
     * Sets the hardware address for given interface
     * @param string $interface Network Interface
     * @param string $address Address to set
     * @return boolean true if change successful, false if not
     * @throws StatusChangeFailedException
     * @throws InvalidAddressException
     */
    public function setHWAddress($interface, $address)
    {
        if ($this->getStatus($interface) == self::STATUS_UP) {
            if (!$this->setStatus($interface, self::STATUS_DOWN)) {
                throw new StatusChangeFailedException(self::STATUS_DOWN);
            }
            $resetStatus = true;
        }

        if (!$this->validMAC($address)) {
            throw new InvalidAddressException($address);
        }

        $cmd = 'hw ether ' . escapeshellarg($address);
        $this->execute($cmd, $interface);

        if ($resetStatus) {
            if (!$this->setStatus($interface, self::STATUS_UP)) {
                throw new StatusChangeFailedException(self::STATUS_UP);
            }
        }
        return ($this->getHWAddress($interface) == $address);
    }
    /**
     * Returns the status of the interface
     * @param string $interface Network Interface
     * @return int Status Constant
     * @throws InvalidInterfaceException
     */
    public function getStatus($interface)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        if (array_key_exists('status', $this->interfaces[$interface])) {
            return $this->interfaces[$interface]['status'];
        } else {
            return self::STATUS_DOWN;
        }
    }
    /**
     * Sets the status of the interface
     * @param string $interface Network Interface
     * @param int $status Status Constant
     */
    public function setStatus($interface, $status)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        switch ($status) {
            case self::STATUS_UP:
                $cmd = 'up';
                break;
            case self::STATUS_DOWN:
                $cmd = 'down add 0.0.0.0';
                $iwconfig = new Iwconfig;
                if ($iwconfig->validInterface($interface)) {
                    $iwconfig->disconnect($interface);
                }
                break;
            default:
                throw new InvalidStatusException($status);
        }

        $this->execute($cmd, $interface);
        $this->forceRefresh();

        return ($this->getStatus($interface) == $status);
    }
    /**
     * Returns the network mask of the interface
     * @param string $interface Network Interface
     * @return string Network Mask of interface
     * @throws InvalidInterfaceException
     */
    public function getNetmask($interface)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        if (array_key_exists('netmask', $this->interfaces[$interface])) {
            return $this->interfaces[$interface]['netmask'];
        } else {
            return '';
        }
    }
    /**
     * Sets the network mask of an interface
     * @param string $interface Network Interface
     * @param string $netmask Network Mask
     * @return boolean true on success, false on failiure
     * @throws InvalidAddressException
     */
    public function setNetmask($interface, $netmask)
    {
        if (!$this->validIP($netmask)) {
            throw new InvalidAddressException($netmask);
        }

        $cmd = 'netmask ' . escapeshellarg($netmask);
        $this->execute($cmd, $interface);
        $this->forceRefresh();

        return ($this->getNetmask($interface) == $netmask);
    }
    /**
     * Returns array of properties of the interface
     * @param string $interface Network Interface
     * @return array Properties of that interface
     * @throws InvalidInterfaceException
     */
    public function getProperties($interface)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        return $this->interfaces[$interface];
    }
    /**
     * Returns a list of valid interfaces
     * @return array List of interfaces
     */
    public function getInterfaceList()
    {
        $this->getData();
        return array_keys($this->interfaces);
    }
    /**
     * Returns true if the given address is a valid IP address
     * @param string $address IP Address
     * @return boolean true if valid, false if not
     */
    public function validIP($address)
    {
        if (ip2long($address)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Tests to see if given address is valid
     * @param string $address MAC Address
     * @return boolean true if valid, false if not
     */
    public function validMAC($address)
    {
        return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $address) == 1);
    }
    /**
     * Tests to see if given interface exists
     * @param string $interface Network Interface
     * @return boolean true if exists, false if not
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
     * Executes ifconfig command on given interface
     * @param string $cmd Command to execute
     * @param string $interface interface to execute on
     * @throws InvalidInterfaceException
     */
    private function execute($cmd, $interface)
    {
        if (!$this->validInterface($interface)) {
            throw new InvalidInterfaceException($interface);
        }

        exec('ifconfig ' . escapeshellarg($interface) . ' ' . $cmd .' 2> /dev/null');
    }
    /**
     * Checks cache for data and loads if necessary
     */
    private function getData()
    {
        if (!$this->dataLoaded) {
            $this->refreshData();
            $this->dataLoaded = true;
        }
    }
    /**
     * Runs ifconfig command and extracts data to array
     */
    private function refreshData()
    {
        $output = [];
        exec('ifconfig -a 2>> /dev/null', $output);
        foreach ($output as $line) {
            $matches = [];
            $firstChar = substr($line, 0, 1);
            if ($firstChar != " " && $firstChar != "") {
                $interface = rtrim(substr($line, 0, 9));
            }
            if (preg_match('/(?<=Link encap:)\w+/', $line, $matches)) {
                $this->interfaces[$interface]['linkType'] = $matches[0];
            }
            if (preg_match('/(?<=HWaddr )(([0-9]|[A-F]){2}:){5}([0-9]|[A-F]){2}/', $line, $matches)) {
                $this->interfaces[$interface]['hwaddr'] = $matches[0];
            }
            if (preg_match('/(?<=inet addr:)[0-9.]+/', $line, $matches)) {
                $this->interfaces[$interface]['address'] = $matches[0];
            }
            if (preg_match('/(?<=Mask:)[0-9.]+/', $line, $matches)) {
                $this->interfaces[$interface]['netmask'] = $matches[0];
            }
            if (preg_match('/UP/', $line, $matches)) {
                $this->interfaces[$interface]['status'] = self::STATUS_UP;
            }
        }
    }
}
