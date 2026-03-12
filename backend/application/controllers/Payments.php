<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payments extends CI_Controller
{
    public function index()
    {
        $this->load->view('upi-payment');
    }
}