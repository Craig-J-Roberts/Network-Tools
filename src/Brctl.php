<?php

namespace cjr\NetworkTools;

class Brctl
{
    public function addbr($bridge)
    {
        $cmd = 'addbr ' . escapeshellarg($bridge);
        $this->execute($cmd, $bridge);
        $this->forceRefresh();

        return in_array($bridge, $this->interfaces);
    }
    
    public function delbr($bridge)
    {
        $cmd = 'delbr ' . escapeshellarg($bridge);
        $this->execute($cmd, $bridge);
        $this->forceRefresh();

        return !in_array($bridge, $this->interfaces);        
    }
    
    public function addif($bridge, $interface)
    {
        $cmd = 'addif ' . escapeshellarg($bridge) . ' ' . escapeshellarg($interface);
        $this->execute($cmd, $interface);
        $this->forceRefresh();

        return in_array($interface, $this->interfaces[$bridge]);         
    }
    
    public function delif($bridge, $interface)
    {
        $cmd = 'delif ' . escapeshellarg($bridge) . ' ' . escapeshellarg($interface);
        $this->execute($cmd, $interface);
        $this->forceRefresh();

        return !in_array($interface, $this->interfaces[$bridge]);          
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
        exec('brctl show 2>> /dev/null', $output);
        foreach ($output as $line) {
            
        }
    }    
}
