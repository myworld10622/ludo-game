<?php
class Pokers extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Poker_model','Users_model']);
    }

    public function index()
    {
        $AllGames = $this->Poker_model->AllGames();
        $data = [
            'title' => 'Poker History',
            'AllGames' => $AllGames,
        ];
        template('poker/index', $data);
    }
    public function Gethistory()
    {
        // error_reporting(-1);
        // ini_set('display_errors', 1);
        // POST data
        $postData = $this->input->post();

        // Get data
        $data = $this->Poker_model->Gethistory($postData);

        echo json_encode($data);
    }
}
