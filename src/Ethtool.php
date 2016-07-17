<?php

namespace cjr\NetworkTools;

class Ethtool
{

    private $interface;
    private $properties = [];
    private $baseProperties = array(
        'connected' => false,
        'speed' => ''
    );

    public function __construct($interface, &$shellCommand)
    {
        $this->interface = $interface;
        $this->shellCommand = $shellCommand;
    }

    public function getSpeed()
    {
        return $this->properties['speed'];
    }

    public function isConnected()
    {
        return $this->properties['connected'];
    }

    private function refreshData()
    {
        $output = $this->shellCommand->execute(array('ethtool', $this->interface));

        $matches = [];
        $this->properties = $this->baseProperties;
        if (preg_match('/(?<=Link detected: yes)/', $output, $matches)) {
            $this->properties['connected'] = true;
        }
        if (preg_match('/(?<=Speed: )[0-10]+[\/\w]+/', $output, $matches)) {
            $this->properties['speed'] = $matches[0];
        }
    }

}
