<?php

class User extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Users_model', 'Setting_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'User Management',
            'Setting' => $this->Setting_model->Setting(),
            // 'AllUser' => $this->Users_model->AllUserList()
        ];
        if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') {
        } else {
            $data['SideBarbutton'] = ['backend/user/add', 'Add Bot'];
        }
        template('user/list', $data);
    }

    public function LadgerReports($id)
    {
        $postData = [];
        $records = $this->Users_model->getUserStatement($id);
        // $records = $this->Users_model->GetAllLogs($id, $postData);
        $data = [
            'title' => 'Ladger Report Management',
            'id' => $id,
            'AllReports' => $records
        ];
        // $data['SideBarbutton'] = ['backend/user/add', 'Add Boat'];
        template('user/ladger_report', $data);
    }

    public function AllLadgerReports()
    {
        $data = [
            'title' => 'Ladger Reports',
        ];
        // $data['SideBarbutton'] = ['backend/user/add', 'Add Boat'];
        template('user/all_ladger_report', $data);
    }


    public function edit($id)
    {
        $data = [
            'title' => 'Add Wallet Amount',
            'User' => $this->Users_model->UserProfile($id)
        ];

        template('user/edit', $data);
    }

    public function edit_user($id)
    {
        $user = $this->Users_model->UserProfile($id);
        $data = [
            'title' => 'Edit User',
            'User' => $user,
            'Referred_User' => $this->Users_model->UserProfile($user[0]->referred_by),
            'BankDetails' => $this->Users_model->AllBankDetails($id),
        ];
        // echo '<pre>';
        // print_r($data);
        // die;
        template('user/edit_user', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add User'
        ];

        template('user/add', $data);
    }

    public function insert()
    {
        $data = [
            'name' => $this->input->post('name'),
            'profile_pic' => 'f_' . rand(1, 3) . '.png',
            'wallet' => $this->input->post('wallet'),
            'user_type' => 1,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $user = $this->Users_model->AddBot($data);
        if ($user) {
            $this->session->set_flashdata('msg', array('message' => 'User Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/user');
    }

    public function sendNotification()
    {
        $userdata = $this->Users_model->AllUserList();

        foreach ($userdata as $key => $value) {
            if (!empty($value->fcm)) {
                $data['msg'] = "you can buy ticket ";
                $data['title'] = "new game";
                push_notification_android($value->fcm, $data);
            }
        }
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
        $data = $this->Users_model->GetUsers($postData, $id, $role);

        echo json_encode($data);
    }

    public function unbindDevice()
    {
        $id = $this->input->post('id');
        $user = $this->Users_model->unbindDevice($id);
        if ($user) {
            $this->session->set_flashdata('msg', array('message' => 'User Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/user');
    }



    public function GetLadgerReports($id)
    {
        $postData = $this->input->post();
        // Get data
        $data = $this->Users_model->GetLadgerReports($id, $postData);

        echo json_encode($data);
    }
    public function getUserBalance($user_id)
    {
        $this->db->select('wallet');
        $this->db->where('id', $user_id);
        $query = $this->db->get('tbl_users');

        // Check if the query returned a result
        if ($query->num_rows() > 0) {
            // Return the user's wallet balance
            return $query->row()->wallet;
        } else {
            // If user is not found, return false or handle accordingly
            return false;
        }
    }


    public function delete($id)
    {
        if ($this->Users_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'User Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/user');

    }



    public function update()
    {
        $UserData = $this->Users_model->UserProfile($this->input->post('user_id'));
        $user = $this->Users_model->UpdateWalletOrder($this->input->post('amount'), $this->input->post('user_id'), $this->input->post('bonus'));
        if ($user) {
            if ($UserData[0]->user_type == 1) {
                direct_admin_profit_statement(ADDED_ADMIN, -$this->input->post('amount'), $this->input->post('user_id'));
            } else if (!empty($this->input->post('bonus'))) {
                direct_admin_profit_statement(ADDED_ADMIN, -$this->input->post('amount'), $this->input->post('user_id'));
            }
            log_statement($this->input->post('user_id'), ADDED_ADMIN, $this->input->post('amount'), 0, 0);
            $user = $this->Users_model->WalletLog($this->input->post('amount'), $this->input->post('bonus'), $this->input->post('user_id'));
            $this->session->set_flashdata('msg', array('message' => 'User Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/user');

    }

    public function update_user()
    {
        $password = $this->input->post('password');
        $md5Password = md5($password); // Convert password to MD5 hash
        $data = [
            'name' => $this->input->post('name'),
            'bank_detail' => $this->input->post('bank_detail'),
            'adhar_card' => $this->input->post('adhar_card'),
            'upi' => $this->input->post('upi'),
            'referral_precent' => $this->input->post('referral_precent'),
            'password' => $password, // Store original password in 'password' column
            'sw_password' => $md5Password, // Store MD5 hashed password in 'sw_password' column
            // 'pin' => $this->input->post('pin'),
            'gender' => $this->input->post('gender'),
            'email' => $this->input->post('email'),
        ];
        $profile_pic = '';
        if (!empty($_FILES["profile_pic"]['name'])) {

            $ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
            $profile_pic = date("Ymd_Hi") . "_" . uniqid() . "." . $ext;

            $config['upload_path'] = 'data/post';
            $config['allowed_types'] = 'gif|jpg|png|jpeg|JPEG';
            $config['file_name'] = $profile_pic;
            //$config['max_size'] = '10000';
            //$config['max_width'] = '2000';
            //$config['max_height'] = '2000';
            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('profile_pic')) {

                $error = array('error' => $this->upload->display_errors());
                // var_dump($error);die;
                redirect('backend/user');
            } else {
                $file = $this->upload->data();
            }
        }
        if ($profile_pic != '') {
            $data['profile_pic'] = $profile_pic;
        }
        // print_r($data);die;
        $user = $this->Users_model->Update($this->input->post('user_id'), $data);
        if ($user) {
            $this->session->set_flashdata('msg', array('message' => 'User Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/user');

    }

    public function view($user_id, $referred_user_id = "")
    {
        $data = [
            'title' => 'View Logs',
            // 'PersonelDetail' => $this->Users_model->getUserData($user_id),
            'AllWins' => $this->Users_model->View_Wins($user_id),
            'AllPurchase' => $this->Users_model->View_Purchase($user_id),

            'AllPurchase_Reffer' => $this->Users_model->View_Purchase_Reffer($user_id),
            'AllWelcome_Reffer' => $this->Users_model->View_Welcome_Reffer($user_id),
            'AllWalletLog' => $this->Users_model->View_WalletLog($user_id),
            'Setting' => $this->Setting_model->Setting(),
            'RummyLog' => $this->Users_model->RummyLog($user_id),
            'RummyPool' => $this->Users_model->RummyPoolLog($user_id),
            'RummyDeal' => $this->Users_model->RummyDealLog($user_id),
            'TeenPattiLog' => $this->Users_model->TeenPattiLog($user_id),
            'DragonWalletAmount' => $this->Users_model->DragonWalletAmount($user_id),
            'WalletAmount' => $this->Users_model->WalletAmount($user_id),
            'SevenUpAmount' => $this->Users_model->SevenUpAmount($user_id),
            'ColorPrediction' => $this->Users_model->ColorPredictionAmount($user_id),
            'CarRoulette' => $this->Users_model->CarRouletteAmount($user_id),
            'AnimalRoulette' => $this->Users_model->AnimalRouletteAmount($user_id),
            'Jackpot' => $this->Users_model->JackpotAmount($user_id),
            'Ludos' => $this->Users_model->getHistory($user_id),
            'HeadTails' => $this->Users_model->HeadTailAmount($user_id),
            'RedBlacks' => $this->Users_model->RedBlack($user_id),
            'Baccarats' => $this->Users_model->BaccaratLog($user_id),
            'JhandiMundas' => $this->Users_model->JhandiMunda($user_id),
            'Roulette' => $this->Users_model->Roulette($user_id),
            'AllPokers' => $this->Users_model->Poker($user_id),
            'AllAviators' => $this->Users_model->Aviator($user_id),

        ];
        if (!empty($referred_user_id)) {
            $data['AllReffer'] = $this->Users_model->View_Reffer_Earn($referred_user_id);
            $data['referred_user_id'] = $referred_user_id;
        } else {
            $data['AllReffer'] = $this->Users_model->View_Reffer_Earn($user_id);
        }

        // echo '<pre>';print_r($data['AllPokers']);die;
        template('user/view', $data);
    }

    public function ChangeStatus()
    {
        $id = $this->input->post('id');
        $status = $this->input->post('status');

        $Change = $this->Users_model->ChangeStatus($id, $status);
        if ($Change) {
            $this->session->set_flashdata('message', array('message' => 'Status Change Successfully', 'class' => 'success'));
        } else {
            $this->session->set_flashdata('message', array('message' => 'Something went to wrong', 'class' => 'success'));
        }
    }

    public function ChangebetlockStatus()
    {
        $id = $this->input->post('id');
        $bet_lock_status = $this->input->post('bet_lock_status');

        $Change = $this->Users_model->ChangebetlockStatus($id, $bet_lock_status);
        if ($Change) {
            $this->session->set_flashdata('message', array('message' => 'Status Change Successfully', 'class' => 'success'));
        } else {
            $this->session->set_flashdata('message', array('message' => 'Something went to wrong', 'class' => 'success'));
        }
    }



    public function active()
    {
        $data = [
            'title' => 'User Management',
            // 'ActiveUser' => $this->Users_model->ActiveUser()
        ];
        // $data['SideBarbutton'] = ['backend/user/add', 'Add Boat'];
        template('user/activeuser', $data);
    }
    public function edit_wallet($id)
    {
        $data = [
            'title' => 'Deduct Wallet Amount',
            'User' => $this->Users_model->UserProfile($id)
        ];

        template('user/edit_wallet', $data);
    }

    public function update_wallet()
    {
        // $user = $this->Users_model->deductWalletOrder($this->input->post('amount'), $this->input->post('user_id'),$this->input->post('bonus'));
        $user = minus_from_wallets($this->input->post('user_id'), $this->input->post('amount'), minus_wallet: 1);
        if ($user) {
            $amount = '-' . $this->input->post('amount');
            direct_admin_profit_statement(ADDED_ADMIN, $this->input->post('amount'), $this->input->post('user_id'));
            log_statement($this->input->post('user_id'), ADDED_ADMIN, $amount, 0, 0);
            $user = $this->Users_model->WalletLog($amount, 0, $this->input->post('user_id'));
            $this->session->set_flashdata('msg', array('message' => 'User Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/user');


    }

    public function getAllLadgerReport()
    {
        // error_reporting(-1);
        // ini_set('display_errors'p, 1);
        // POST data
        $postData = $this->input->post();

        // Get data
        $data = $this->Users_model->AllLadgerReport($postData);

        echo json_encode($data);
    }
}