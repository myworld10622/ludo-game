<?php

defined('BASEPATH') or exit('No direct script access allowed');

use DeviceDetector\DeviceDetector;


class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Setting_model');
        $this->load->model('Banner_model');
    }

    public function index()
    {
         if ($_ENV['ENVIRONMENT']== 'demo') {
            redirect('backend');
         }

        $data = [
            'title' => t('nav_home'),
            'banner' => $this->Banner_model->view(),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/index', $data);
    }

    public function betreeno()
    {
        $data = [
            'title' => 'Betreeno - Card Game Winning Rules'
        ];
        $this->load->view('website/betreeno', $data);
    }

    public function download()
    {
        $data = [
            'title' => t('nav_download'),
            'banner' => $this->Banner_model->view(),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/download', $data);
    }

    public function faq()
    {
        $data = [
            'title' => t('nav_faq'),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/faq', $data);
    }

    public function about_us()
    {
        $data = [
            'title' => t('nav_about'),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/about-us', $data);
    }

    public function refund_policy()
    {
        $data = [
            'title' => t('nav_refund'),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/refund-policy', $data);
    }

    public function privacy_policy()
    {
        $data = [
            'title' => t('nav_privacy'),
            'Setting' => $this->Setting_model->Setting(),
        ];

        website('website/privacy', $data);
    }

    public function terms_conditions()
    {
        $data = [
            'title' => t('nav_terms'),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/t-and-c', $data);
    }

    public function security()
    {
        $data = [
            'title' => t('nav_security'),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/security', $data);
    }

    public function contact_us()
    {
        $data = [
            'title' => t('nav_contact'),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/Contact', $data);
    }

    public function download2()
    {
        $data = [
            'title' => t('nav_download'),
            'banner' => $this->Banner_model->view(),
            'Setting' => $this->Setting_model->Setting(),
        ];
        website('website/download-2', $data);
    }

    public function delete_account()
    {
        $data = [
            'title' => 'Delete Account',
            // 'Setting' => $this->Setting_model->Setting(),
        ];
        // website('website/delete-account', $data);
        $this->load->view('delete-account');

    }

    public function deleteAccount()
    {
        $this->load->model('Users_model');
        $mobile=$this->input->post('mobile');
        $result=$this->Users_model->DeleteUser($mobile);
        if($result){
            $this->session->set_flashdata('msg', array('message' => 'User Deleted Successfully', 'class' => 'success', 'position' => 'top-right'));
        }else{
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'danger', 'position' => 'top-right'));
        }
        // website('website/delete-account', $data);
        redirect('delete-account','refresh');
    }

    public function log_device_info()
    {
        $this->load->library('user_agent');
        $this->load->library('mobile_detect');

        // Capture device and browser information
        $isMobile = $this->mobile_detect->isMobile() ? 'Mobile' : 'Not Mobile';
        $isTablet = $this->mobile_detect->isTablet() ? 'Tablet' : 'Not Tablet';
        $osName = $this->mobile_detect->getOperatingSystem();
        $browserName = $this->agent->browser();
        $ipAddress = $this->input->ip_address();
        $sessionToken = bin2hex(random_bytes(32)); // Generate a unique session token

        // Initialize user_id (assume it's set correctly in your actual code)
        $user_id = $this->session->userdata('user_id'); // Example of getting user_id from session

        $userAgent = $this->input->user_agent();
        $deviceName = $this->getDeviceDetails($userAgent);

        // Prepare session details for database insertion
        $data = array(
            'user_id' => $user_id,
            'device_name' => $deviceName,
            'device_type' => $isMobile . ($isTablet ? ' & ' . $isTablet : ''),
            'os_name' => $osName,
            'browser_name' => $browserName,
            'ip_address' => $ipAddress,
            'session_token' => $sessionToken,
            'last_active' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        );

        // Debug output (for development purposes)
        print_r($data);

        // Uncomment and adjust database insertion
        // $this->load->database();
        // if ($this->db->insert('user_devices', $data)) {
        //     echo "Device information stored successfully!";
        // } else {
        //     echo "Error storing device information.";
        // }
    }

    function getDeviceDetails($userAgent) {
        $dd = new DeviceDetector($userAgent);
        $dd->parse();
    
        if ($dd->isMobile()) {
            return $dd->getDeviceName();
        } elseif ($dd->isTablet()) {
            return $dd->getDeviceName();
        } else {
            return 'Desktop or Unknown Device';
        }
    }

}
