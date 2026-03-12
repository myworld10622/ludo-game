<?php
class Target extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Target_model','Users_model']);
    }

    public function index()
    {
        $AllGames = $this->Target_model->AllGames();
        $RandomFlag = $this->Target_model->getRandomFlag('target_random');
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->Target_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Target History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->target_random
        ];
        template('target/index', $data);
    }

    public function target_bet($id){

        $AllUsers = $this->Target_model->ViewBet('',$id);
        foreach ($AllUsers as $key => $value) {
            $user_details= $this->Users_model->UserProfile($value->user_id);
            $AllUsers[$key]->user_name='';
            if($user_details){
                $AllUsers[$key]->user_name=$user_details[0]->name;
            }else{
                $AllUsers[$key]->user_name='';
            }
        }
        $data = [
            'title' => 'Target History',
            'AllUsers' => $AllUsers
        ];
        template('target/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->Target_model->ChangeStatus();
        if ( $Change ) {
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
        $data = $this->Target_model->GetUsers($postData);

        echo json_encode($data);
    }

}
