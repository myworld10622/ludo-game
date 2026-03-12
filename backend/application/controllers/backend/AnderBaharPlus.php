<?php

class AnderBaharPlus extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['AnderBaharPlus_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $AllGames = $this->AnderBaharPlus_model->AllGames();
        $RandomFlag = $this->AnderBaharPlus_model->getRandomFlag('ander_bahar_plus_random');
        $setting = $this->Setting_model->setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->AnderBaharPlus_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'AnderBaharPlus History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->ander_bahar_plus_random,
            'setting' => $setting->ander_bahar_plus_min_bet
        ];
        template('AnderBaharPlus/index', $data);
    }

    public function anderbaharplus_bet($id)
    {

        $AllUsers = $this->AnderBaharPlus_model->ViewBet('', $id);
        foreach ($AllUsers as $key => $value) {
            $user_details= $this->Users_model->UserProfile($value->user_id);
            $AllUsers[$key]->user_name='';
            if($user_details) {
                $AllUsers[$key]->user_name=$user_details[0]->name;
            } else {
                $AllUsers[$key]->user_name='';
            }
        }
        $data = [
            'title' => 'AnderBaharPlus History',
            'AllUsers' => $AllUsers
        ];
        template('AnderBaharPlus/show_details', $data);
    }
    public function ChangeStatus()
    {

        $Change = $this->AnderBaharPlus_model->ChangeStatus();
        if ($Change) {
            echo 'true';
        } else {
            echo 'false';
        }

    }

    public function GetUsers()
    {
        // error_reporting(-1);
        // ini_set('display_errors', 1);
        // POST data
        $postData = $this->input->post();

        // Get data
        $data = $this->AnderBaharPlus_model->GetUsers($postData);

        echo json_encode($data);
    }

    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->AnderBaharPlus_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }

}