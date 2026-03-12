<?php

class WithdrawlDashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['WithdrawlDashboard_model']);
        if (WITHDRAWL_DASHBOARD == false) {
            exit;
        }
    }

    // public function index()
    // {
    //     redirect('backend/dashboard/admin');
    // }

    public function index()
    {
        $data = [
            'title' => 'Dashboard',
            'ApprovedCoins' => $this->WithdrawlDashboard_model->WithDrawalAmount(1),
            'PendingCoins' => $this->WithdrawlDashboard_model->WithDrawalAmount(0),
            'RejectedCoins' => $this->WithdrawlDashboard_model->WithDrawalAmount(2),
            'PurchaseOnline' => $this->WithdrawlDashboard_model->PurchaseOnline(),
            'PurchaseOffline' => $this->WithdrawlDashboard_model->PurchaseOffline(),
            'RobotCoin' => $this->WithdrawlDashboard_model->RobotCoin(),
            'WelcomeBonus' => $this->WithdrawlDashboard_model->WelcomeBonus(),
            'WelcomeReferralBonus' => $this->WithdrawlDashboard_model->WelcomeRefferalBonus(),
            'RefferalBonus' => $this->WithdrawlDashboard_model->RefferalBonus(),
            'PurchaseBonus' => $this->WithdrawlDashboard_model->PurchaseBonus(),
            'TotalWallet' => $this->WithdrawlDashboard_model->TotalWallet(),
            'TotalWinning' => $this->WithdrawlDashboard_model->TotalWinning(),

        ];
        // $data['ActiveUser'];
        // exit;
        template('withdrawl_dashboard/withdrawl_dashboard', $data);
    }
}
