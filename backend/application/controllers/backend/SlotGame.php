<?php
class SlotGame extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['SlotGame_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $AllGames = $this->SlotGame_model->AllGames();
        $RandomFlag = $this->SlotGame_model->getRandomFlag('up_down_random');
        $setting = $this->Setting_model->setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->SlotGame_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
       $data = [
    'title' => 'Slot Game History',
    'AllGames' => $AllGames,
    'RandomFlag' => isset($RandomFlag->up_down_random) ? $RandomFlag->up_down_random : 0,
    'setting' => isset($setting->slot_game_min_bet) ? $setting->slot_game_min_bet : 0
];

        template('slot_game/index', $data);
    }

    public function slot_game_bet($id){

        $AllUsers = $this->SlotGame_model->ViewBet('',$id);
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
        template('slot_game/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->SlotGame_model->ChangeStatus();
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
        $data = $this->SlotGame_model->Gethistory($postData);

        echo json_encode($data);
    }

    
    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->SlotGame_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }
}