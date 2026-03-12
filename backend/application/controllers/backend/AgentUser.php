<?php
class AgentUser extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['AgentUser_model', 'Users_model', 'Agent_model']);

    }

    public function index()
    {
        $id = $this->session->userdata('admin_id');
        $data = [
            'title' => 'Users Management',
            'AllAgent' => $this->AgentUser_model->AllAgentUserList($id)
        ];
        $data['SideBarbutton'] = ['backend/AgentUser/add', 'Add User'];
        template('agent_user/index', $data);
    }

    public function GetUsers()
    {
        // error_reporting(-1);
        // ini_set('display_errors', 1);    
        // POST data
        $id = $this->session->userdata('admin_id');
        $role = $this->session->userdata('role');

        $postData = $this->input->post();

        // Get data
        $data = $this->AgentUser_model->GetUsers($postData, $id, $role);

        echo json_encode($data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Agent Users'
        ];
        template('agent_user/add', $data);
    }

    // public function insert()
    // {

    //     $mobile_number = $this->input->post('mobile');

    //     // Check if the mobile number already exists in the database
    //     if ($this->AgentUser_model->isMobileNumberExists($mobile_number)) {
    //         // Mobile number already exists, show error message
    //         $this->session->set_flashdata('msg', array('message' => 'Mobile number already exists', 'class' => 'error', 'position' => 'top-right'));
    //         redirect('backend/AgentUser');
    //     }
    //     $data = [
    //         'name' => $this->input->post('name'),
    //         'password' => $this->input->post('password'),
    //         'mobile' => $this->input->post('mobile'),
    //         // 'email' => $this->input->post('email'),
    //         // 'profile_pic' => 'f_' . rand(1, 3) . '.png',
    //         // 'wallet' => $this->input->post('wallet'),
    //         // 'user_type' => 1,
    //         // 'gender' => $gender, 
    //         'added_date' => date('Y-m-d H:i:s'),
    //         'created_by' => $this->session->userdata('admin_id'),

    //     ];
    //     $agentuser = $this->AgentUser_model->AddagentUser($data);
    //     if ($agentuser) {
    //         $this->session->set_flashdata('msg', array('message' => 'AgentUser Added Successfully', 'class' => 'success', 'position' => 'top-right'));
    //     } else {
    //         $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
    //     }
    //     redirect('backend/AgentUser');
    // }

    public function insert()
    {
        $name = $this->input->post('name');
        $password = $this->input->post('password');
        $email = $this->input->post('email');
        $mobile_number = $this->input->post('mobile');
        $gender = $this->input->post('gender');
        // unique token generated
        $token = md5(uniqid(rand(), true));

        if (mb_strlen($mobile_number) > 10) {
            $this->session->set_flashdata('msg', array('message' => 'Please Enter Valid length', 'class' => 'error', 'position' => 'top-right'));
            redirect('backend/AgentUser');
        }

        if ($this->AgentUser_model->isMobileNumberExists($mobile_number)) {
            $this->session->set_flashdata('msg', array('message' => 'Mobile number already exists', 'class' => 'error', 'position' => 'top-right'));
            redirect('backend/AgentUser');
        }



        $setting = $this->Users_model->Setting();
        $data = [
            'name' => $name,
            'password' => $password,
            'mobile' => $mobile_number,
            'email' => $email,
            'gender' => $gender,
            'profile_pic' => 'f_' . rand(1, 3) . '.png',
            'bonus_wallet' => $setting->bonus_amount,
            'token' => $token,
            'added_date' => date('Y-m-d H:i:s'),
            'created_by' => $this->session->userdata('admin_id'),
        ];

        $agentuser_id = $this->AgentUser_model->AddagentUser($data);

        if ($agentuser_id) {
            $setting = $this->AgentUser_model->Setting();
            $this->AgentUser_model->UpdateReferralCode($agentuser_id, $setting->referral_id);
            $this->session->set_flashdata('msg', array('message' => 'AgentUser Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/AgentUser');
    }

    public function edit_user($id)
    {
        $data = [
            'title' => 'Edit User',
            'User' => $this->Users_model->UserProfile($id)
        ];
        // echo '<pre>';print_r($data);die;
        template('agent_user/edit', $data);
    }

    public function update_user()
    {
        $password = $this->input->post('password');
        $md5Password = md5($password); // Convert password to MD5 hash
        $data = [
            'name' => $this->input->post('name'),
            'password' => $password, // Store original password in 'password' column
            'sw_password' => $md5Password,
            // 'gender' => $this->input->post('gender'),
            // 'email' => $this->input->post('email'),
        ];

        $user = $this->Users_model->Update($this->input->post('user_id'), $data);
        if ($user) {
            $this->session->set_flashdata('msg', array('message' => 'User Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/AgentUser');

    }


    public function edit_wallet($id)
    {
        $data = [
            'title' => 'Add Wallet Amount',
            'User' => $this->Users_model->UserProfile($id)
        ];

        template('agent_user/agentadd_wallet', $data);
    }

    public function update_wallet()
    {
        $amount = $this->input->post('amount');
        $user_id = $this->input->post('user_id');
        $adminId = $this->session->userdata('admin_id');
        $user_wallet_balance = $this->Agent_model->getAgentBalance($adminId);
        // echo $user_wallet_balance;die;

        if ($user_wallet_balance !== false && $user_wallet_balance >= $amount) {
            $user = $this->Users_model->UpdateWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
            if ($user) {
                $this->Users_model->WalletLog($this->input->post('amount'), 0, $this->input->post('user_id'));
                log_statement($this->input->post('user_id'), AGENT_ADDED, $this->input->post('amount'), 0, 0);
                $agent = $this->Agent_model->DeductWalletOrder($this->input->post('amount'), $adminId);
                if ($agent) {
                    $this->Agent_model->WalletLog('-' . $this->input->post('amount'), 0, $adminId);
                }
                $this->session->set_flashdata('msg', array('message' => 'Agent Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
            }

            redirect('backend/AgentUser');
        }

        $this->session->set_flashdata('msg', array('message' => 'Insufficient funds', 'class' => 'error', 'position' => 'top-right'));
        redirect('backend/AgentUser');
    }


    public function deduct_wallet($id)
    {
        $data = [
            'title' => 'Deduct Wallet Amount',
            'User' => $this->Users_model->UserProfile($id)
        ];

        template('agent_user/deduct_wallet', $data);
    }


    public function update_deduct_wallet()
    {

        $adminId = $this->session->userdata('admin_id');
        $user = $this->Users_model->DeductWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
        if ($user) {
            $user = $this->Users_model->WalletLog('-' . $this->input->post('amount'), 0, $this->input->post('user_id'));
            log_statement($this->input->post('user_id'), AGENT_ADDED, -$this->input->post('amount'), 0, 0);
            // $agent = $this->Agent_model->UpdateWalletOrder($this->input->post('amount'), $adminId);
            // if ($agent) {
            //     $this->Agent_model->WalletLog('-'.$this->input->post('amount'), 0, $adminId);
            // }
            $this->session->set_flashdata('msg', array('message' => 'Agent Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/AgentUser');
    }


    public function delete($id)
    {
        if ($this->AgentUser_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'AgentUser Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/AgentUser');
    }


}
