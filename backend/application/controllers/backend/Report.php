<?php
class Report extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Report_model']);
    }

    public function index()
    {
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date'); 
        $Recharge= $this->Report_model->recharge_entry($startDate,$endDate);
       
        $data = [
            'title' => 'Recharge Managment',
            'Recharge' => $Recharge,
            // 'RandomFlag'=>$RandomFlag->red_black_random
        ];

        // echo "<pre>";
        // print_r($Recharge);
        // die;
        template('report/recharge', $data);
    }
}

