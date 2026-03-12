<?php
class SevenUp extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['SevenUp_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $AllGames = $this->SevenUp_model->AllGames();
        $RandomFlag = $this->SevenUp_model->getRandomFlag('up_down_random');
        $setting = $this->Setting_model->setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->SevenUp_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Seven Up Down History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->up_down_random,
            'setting' => $setting->seven_up_min_bet
        ];
        template('seven_up/index', $data);
    }

    public function seven_up_bet($id){

        $AllUsers = $this->SevenUp_model->ViewBet('',$id);
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
        template('seven_up/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->SevenUp_model->ChangeStatus();
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
        $data = $this->SevenUp_model->Gethistory($postData);

        echo json_encode($data);
    }

    
    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->SevenUp_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }
}