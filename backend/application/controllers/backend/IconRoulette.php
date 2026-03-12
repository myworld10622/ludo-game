<?php

class IconRoulette extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['IconRoulette_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $AllGames = $this->IconRoulette_model->AllGames();
        $RandomFlag = $this->IconRoulette_model->getRandomFlag('icon_roulette_random');
        $setting = $this->Setting_model->setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->IconRoulette_model->ViewBet('', $value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Icon Roulette History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->icon_roulette_random,
            'setting' => $setting->icon_roullette_min_bet
        ];
        template('icon_roulette/index', $data);
    }

    public function icon_roulette_bet($id)
    {
        $AllUsers = $this->IconRoulette_model->ViewBet('', $id);
        foreach ($AllUsers as $key => $value) {
            $user_details= $this->Users_model->UserProfile($value->user_id);
            if ($user_details) {
                $AllUsers[$key]->user_name=$user_details[0]->name;
            } else {
                $AllUsers[$key]->user_name='';
            }
        }
        $data = [
            'title' => 'Game History',
            'AllUsers' => $AllUsers
        ];
        template('icon_roulette/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->IconRoulette_model->ChangeStatus();
        if ( $Change ) {
            echo 'true';
        } else {
            echo 'false';
        }
       
    }
    public function Gethistory()
    {
        // error_reporting(-1);
        // ini_set('display_errors', 1);
        // POST data
        $postData = $this->input->post();

        // Get data
        $data = $this->IconRoulette_model->Gethistory($postData);

        echo json_encode($data);
    }

    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->IconRoulette_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }
}