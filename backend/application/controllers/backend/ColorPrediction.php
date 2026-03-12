<?php
class ColorPrediction extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ColorPrediction_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date'); 
        $AllGames = $this->ColorPrediction_model->AllGames($startDate, $endDate);
        $RandomFlag = $this->ColorPrediction_model->getRandomFlag('color_prediction_random');
        $setting = $this->Setting_model->Setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->ColorPrediction_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($setting->color_prediction_min_bet);die;
        $data = [
            'title' => 'Color Prediction 15 Sec History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->color_prediction_random,
            'setting' => $setting->color_prediction_min_bet
        ];
        template('color_prediction/index', $data);
    }

    public function color_prediction_bet($id){

        $AllUsers = $this->ColorPrediction_model->ViewBet('',$id);
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
        template('color_prediction/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->ColorPrediction_model->ChangeStatus();
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
        $data = $this->ColorPrediction_model->Gethistory($postData);

        echo json_encode($data);
    }

    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->ColorPrediction_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }
}