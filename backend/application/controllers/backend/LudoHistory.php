<?php

class LudoHistory extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['LudoTableMaster_model']);
    }


    public function index()
    {
        // $AllGames = $this->LudoTableMaster_model->getHistory();
        $data = [
            'title' => 'Ludo History',
            // 'AllGames' => $AllGames
        ];
        template('ludo_table_master/history', $data);
    }
    public function Gethistory()
    {
        // error_reporting(-1);
        // ini_set('display_errors', 1);
        // POST data
        $postData = $this->input->post();

        // Get data
        $data = $this->LudoTableMaster_model->Gethistory($postData);

        echo json_encode($data);
    }
  
}