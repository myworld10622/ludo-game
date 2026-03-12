<?php

class Rummy extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Rummy_model', 'Users_model']);
    }

    public function index()
    {
        $AllGames = $this->Rummy_model->AllGames();

        $data = [
            'title' => 'Rummy Point History',
            'AllGames' => $AllGames
        ];
        template('rummy/index', $data);
    }

    public function ChangeStatus()
    {

        $Change = $this->Rummy_model->ChangeStatus();
        if ($Change) {
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
        $data = $this->Rummy_model->Gethistory($postData);

        echo json_encode($data);
    }
}