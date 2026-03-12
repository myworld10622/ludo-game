<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// require_once APPPATH . 'third_party/vendor/autoload.php'; // Include Composer autoload

use Detection\MobileDetect;

class Mobile_detect {
    protected $detect;

    public function __construct() {
        $this->detect = new MobileDetect;
    }

    public function isMobile() {
        return $this->detect->isMobile();
    }

    public function isTablet() {
        return $this->detect->isTablet();
    }

    public function getOperatingSystem() {
        return $this->detect->version('OS');
    }

    public function getBrowser() {
        return $this->detect->version('Browser');
    }
}
