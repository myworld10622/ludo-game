<?php
class ColorPrediction3Min extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ColorPrediction3Min_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');
        $AllGames = $this->ColorPrediction3Min_model->AllGames($startDate, $endDate);
        $RandomFlag = $this->ColorPrediction3Min_model->getRandomFlag('color_prediction_3_min_random');
        $setting = $this->Setting_model->Setting();
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->ColorPrediction3Min_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Color Prediction 3 Min History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->color_prediction_3_min_random,
            'setting' => $setting->color_prediction_3min_min_bet
        ];
        template('color_prediction_3_min/index', $data);
    }

    public function color_prediction_bet($id){

        $AllUsers = $this->ColorPrediction3Min_model->ViewBet('',$id);
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
        template('color_prediction_3_min/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->ColorPrediction3Min_model->ChangeStatus();
        if ( $Change ) {
            echo 'true';
        } else {
            echo 'false';
        }
       
    }

    public function set_withdraw_amount()
    {
        $amount = $this->input->post('amount');
        echo 'Amount received: ' . $amount;
        $data = $this->ColorPrediction3Min_model->set_withdraw_amount($amount);
        echo json_encode($data);

    }

    public function Gethistory() 
    {
        // error_reporting(-1);
        // ini_set('display_errors', 1);
        // POST data
        $postData = $this->input->post();
        // print_r('hello');exit;
        // echo 'hello';exit;

        // Get data
        $data = $this->ColorPrediction3Min_model->Gethistory($postData);

        echo json_encode($data);
    }
}