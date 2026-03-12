<?php
class JhandiMunda extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['JhandiMunda_model','Users_model']);
    }

    public function index()
    {
        $AllGames = $this->JhandiMunda_model->AllGames();
        $RandomFlag = $this->JhandiMunda_model->getRandomFlag('jhandi_munda_random');
        // foreach ($AllGames as $key => $value) {
        //     $AllGames[$key]->details=$this->JhandiMunda_model->ViewBet('',$value->id);
        // }
        // echo '<pre>';print_r($AllGames);die;
        $data = [
            'title' => 'Jhandi Munda History',
            'AllGames' => $AllGames,
            'RandomFlag'=>$RandomFlag->jhandi_munda_random
        ];
        template('jhandi_munda/index', $data);
    }

    public function JhandiMundaBet($id){

        $AllUsers = $this->JhandiMunda_model->ViewBet('',$id);
        foreach ($AllUsers as $key => $value) {
            $user_details= $this->Users_model->UserProfile($value->user_id);
            if($user_details){
                $AllUsers[$key]->user_name=$user_details[0]->name;
            }else{
                $AllUsers[$key]->user_name='';
            }
        }
        $data = [
            'title' => 'Jhandi Munda History',
            'AllUsers' => $AllUsers
        ];
        template('jhandi_munda/show_details', $data);
    }
    public function ChangeStatus() {
        
        $Change = $this->JhandiMunda_model->ChangeStatus();
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
        $data = $this->JhandiMunda_model->Gethistory($postData);

        echo json_encode($data);
    }
}
