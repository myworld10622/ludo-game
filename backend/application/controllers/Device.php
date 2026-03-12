<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Device extends CI_Controller
{
    public function index()
    {
        // Load a view or return a response
        $this->load->view('device');
    }
}
