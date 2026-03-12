<?php
class AnderBahar extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['AnderBahar_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $AllGames = $this->AnderBahar_model->AllGames();
        $RandomFlag = $this->AnderBahar_model->getRandomFlag('ander_bahar_random');
        $setting = $this->Setting_model->setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->AnderBahar_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Andar Bahar History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->ander_bahar_random,
            'setting' => $setting->ander_bahar_min_bet
        ];
        template('ander_baher/index', $data);
    }

    public function ander_baher_bet($id){

        $AllUsers = $this->AnderBahar_model->ViewBet('',$id);
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
        template('ander_baher/show_details', $data);
    }

    public function ChangeStatus() {
        
        $Change = $this->AnderBahar_model->ChangeStatus();
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
        $data = $this->AnderBahar_model->Gethistory($postData);

        echo json_encode($data);
    }

}