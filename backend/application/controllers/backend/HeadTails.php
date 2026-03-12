<?php
class HeadTails extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['HeadTail_model','Users_model']);
    }

    public function index()
    {
        $AllGames = $this->HeadTail_model->AllGames();
        $RandomFlag = $this->HeadTail_model->getRandomFlag('head_tail_random');
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->HeadTail_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Head & Tail History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->head_tail_random
        ];
        template('head_tails/index', $data);
    }

    public function HeadTailsBet($id){

        $AllUsers = $this->HeadTail_model->ViewBet('',$id);
        foreach ($AllUsers as $key => $value) {
            $user_details= $this->Users_model->UserProfile($value->user_id);
            if($user_details){
                $AllUsers[$key]->user_name=$user_details[0]->name;
            }else{
                $AllUsers[$key]->user_name='';
            }
        }
        $data = [
            'title' => 'Head & Tails History',
            'AllUsers' => $AllUsers
        ];
        template('head_tails/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->HeadTail_model->ChangeStatus();
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
        $data = $this->HeadTail_model->Gethistory($postData);

        echo json_encode($data);
    }

    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->HeadTail_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }
    
}