<?php

class AnimalRoulette extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['AnimalRoulette_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $AllGames = $this->AnimalRoulette_model->AllGames();
        $RandomFlag = $this->AnimalRoulette_model->getRandomFlag('animal_roulette_random');
        $setting = $this->Setting_model->setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->AnimalRoulette_model->ViewBet('', $value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Animal Roulette History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->animal_roulette_random,
            'setting' => $setting->animal_roullette_min_bet
        ];
        template('animal_roulette/index', $data);
    }

    public function animal_roulette_bet($id)
    {
        $AllUsers = $this->AnimalRoulette_model->ViewBet('', $id);
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
        template('animal_roulette/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->AnimalRoulette_model->ChangeStatus();
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
        $data = $this->AnimalRoulette_model->Gethistory($postData);

        echo json_encode($data);
    }

    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->AnimalRoulette_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }
}