<?php
class Roulette extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Roulette_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $AllGames = $this->Roulette_model->AllGames();
        $RandomFlag = $this->Roulette_model->getRandomFlag('roulette_random');
        $setting = $this->Setting_model->setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->Roulette_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Roulette History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->roulette_random,
            'setting' => $setting->roullette_min_bet

        ];
        template('roulette/index', $data);
    }

    public function RouletteBet($id){

        $AllUsers = $this->Roulette_model->ViewBet('',$id);
        foreach ($AllUsers as $key => $value) {
            $user_details= $this->Users_model->UserProfile($value->user_id);
            if($user_details){
                $AllUsers[$key]->user_name=$user_details[0]->name;
            }else{
                $AllUsers[$key]->user_name='';
            }
        }
        $data = [
            'title' => 'Roulette History',
            'AllUsers' => $AllUsers
        ];
        template('roulette/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->Roulette_model->ChangeStatus();
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
        $data = $this->Roulette_model->Gethistory($postData);

        echo json_encode($data);
    }

    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->Roulette_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }
}
