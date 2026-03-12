<?php
class Lottery extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Lottery_model','Users_model']);
    }

    public function index()
    {
        $data=[
            'title' => 'Lottery History',
            'AllGames' => $this->Lottery_model->AllGames()
        ];
        template('lottery/index',$data);
    }

    public function Gethistory()
    {
        // error_reporting(-1);
        // ini_set('display_errors', 1);
        // POST data
        $postData = $this->input->post();

        // Get data
        $data = $this->Lottery_model->Gethistory($postData);

        echo json_encode($data);
    }

    public function LotteryBet($id){

        $AllUsers = $this->Lottery_model->ViewBet('',$id);
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
            'title' => 'Game History',
            'AllUsers' => $AllUsers
        ];
        // echo '<pre>';
        // print_r($data);
        // exit;
        template('lottery/show_details', $data);
    }
}