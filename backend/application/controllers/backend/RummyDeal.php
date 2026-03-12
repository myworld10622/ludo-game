<?php
class RummyDeal extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['RummyDeal_model', 'Users_model']);
    }

    public function index()
    {
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');

        $AllGames = $this->RummyDeal_model->AllGames($startDate, $endDate);

        $data = [
            'title' => 'Rummy Deal History',
            'AllGames' => $AllGames,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        template('rummy_deal/index', $data);
    }
}