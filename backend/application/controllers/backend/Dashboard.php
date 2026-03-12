<?php

class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Setting_model', 'Users_model', 'Coin_plan_model', 'AgentUser_model', 'Agent_model', 'WithdrawalLog_model']);
    }

    public function index()
    {
        redirect('backend/dashboard/admin');
    }

    public function admin()
    {
        $role = $this->session->userdata("role");
        $adminId = $this->session->userdata("admin_id");

        if ($role == "2") {
            $data = [
                'title' => 'Agent Dashboard',
                'ActiveUser' => $this->AgentUser_model->AllAgentUserList($adminId),
                'AdminCoins' => $this->Agent_model->getAgentBalance($adminId),
            ];
        } else if ($role == DISTRIBUTOR) {
            $data = [
                'title' => 'Dashboard',
                'AllAgent' => $this->Agent_model->AllAgentList(),
                'AdminCoins' => $this->Agent_model->getDistributprBalance($adminId),
            ];
        } else {
            $data = [
                'title' => 'Dashboard',
                'AdminCoins' => $this->Setting_model->Setting()->admin_coin,
                'JackpotCoins' => $this->Setting_model->Setting()->jackpot_coin,
                'JackpotStatus' => $this->Setting_model->Setting()->jackpot_status,
                'RummyBotStatus' => $this->Setting_model->Setting()->robot_rummy,
                'TeenpattiBotStatus' => $this->Setting_model->Setting()->robot_teenpatti,
                'ActiveUser' => $this->Users_model->ActiveUser(),
                'AllUserList' => $this->Users_model->AllUserList(),
                'TotalCoins' => $this->Coin_plan_model->GetTotalPurchase(1), // Approved deposit
                'TodayCoins' => $this->Coin_plan_model->GetTodayPurchase(),
                'PendingCoins' => $this->Coin_plan_model->GetTotalPurchase(0), // Pending deposit
                'RejectedCoins' => $this->Coin_plan_model->GetTotalPurchase(2), // Rejected deposit
                'TotalWithdraw' => $this->WithdrawalLog_model->GetTotalWithdraw(1), // Approved withdraw
                'TodayWithdraw' => $this->WithdrawalLog_model->GetTodayWithdraw(),
                'PendingWithdraw' => $this->WithdrawalLog_model->GetTotalWithdraw(0), // Pending withdraw
                'RejectedWithdraw' => $this->WithdrawalLog_model->GetTotalWithdraw(2), // Rejected withdraw
                'BotBalance' => $this->Users_model->TotalBotBalance(),
                'TodayNewUsers' => $this->Users_model->TodayNewUsers(),
            ];

        }
        // echo '<pre>';
        // print_r($data['PendingCoins']);
        // exit();
        $data['search_user'] = null;
        $Mobile = $this->input->get('mobile');
        if ($Mobile) {
            $data['search_user'] = $this->Users_model->UserByMobile($Mobile);
        }

        $data['role'] = $role;
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('dashboard/manufacturer', $data);
    }

    public function searchUser()
    {
        $mobile = $this->input->post('mobile');

        if (!empty($mobile)) {
            $user = $this->Users_model->UserByMobile($mobile);
            // print_r($user);exit();
            if ($user) {
                $response = ['status' => true, 'data' => $user];
            } else {
                $response = ['status' => false, 'message' => 'User not found'];
            }
        } else {
            $response = ['status' => false, 'message' => 'Invalid request'];
        }

        echo json_encode($response);
    }
}