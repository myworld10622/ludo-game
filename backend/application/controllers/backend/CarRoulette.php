<?php
class CarRoulette extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['CarRoulette_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $AllGames = $this->CarRoulette_model->AllGames();
        $RandomFlag = $this->CarRoulette_model->getRandomFlag('car_roulette_random');
        $setting = $this->Setting_model->setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->CarRoulette_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Car Roulette History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->car_roulette_random,
            'setting' => $setting->car_roullette_min_bet
        ];
        // print_r($data['setting']);exit;
        template('car_roulette/index', $data);
    }

    public function car_roulette_bet($id){

        $AllUsers = $this->CarRoulette_model->ViewBet('',$id);
        foreach ($AllUsers as $key => $value) {
            $user_details= $this->Users_model->UserProfile($value->user_id);
            if($user_details){
                $AllUsers[$key]->user_name=$user_details[0]->name;
            }else{
                $AllUsers[$key]->user_name='';
            }
        }
        $data = [
            'title' => 'Game History',
            'AllUsers' => $AllUsers
        ];
        template('car_roulette/show_details', $data);
    }

    public function ChangeStatus() {
        
        $Change = $this->CarRoulette_model->ChangeStatus();
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
        $data = $this->CarRoulette_model->Gethistory($postData);

        echo json_encode($data);
    }

    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->CarRoulette_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }

}
