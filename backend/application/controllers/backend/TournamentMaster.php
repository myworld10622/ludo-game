<?php
class TournamentMaster extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['TournamentMaster_model', 'TournamentTypes_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Rummy Tournament Master',
            'AllTournamentMaster' => $this->TournamentMaster_model->AllTournamentMasterList()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/TournamentMaster/add', 'Add Tournament Master'];
        }
        template('tournament_master/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Tournament Master',
            'AllTournamentTypes' => $this->TournamentTypes_model->AllTournamentTypesList(),
        ];

        template('tournament_master/add', $data);
    }

    public function edit($id)
    {
        // print_r($id);
        // exit();
        $data = [
            'title' => 'Edit Tournament Master',
            'TournamentMaster' => $this->TournamentMaster_model->ViewTournamentMaster($id),
            'AllTournamentTypes' => $this->TournamentTypes_model->AllTournamentTypesList(),
        ];

        template('tournament_master/edit', $data);
    }

    public function delete($id)
    {
        if ($this->TournamentMaster_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Teen Patti Table Master Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/TournamentMaster');
    }

    public function insert()
    {
        $data = [
            'registration_start_date' => $this->input->post('registration_start_date'),
            'registration_start_time' => $this->input->post('registration_start_time'),
            'start_date' => $this->input->post('start_date'),
            'start_time' => $this->input->post('start_time'),
            'max_entry_pass' => $this->input->post('max_entry_pass'),
            'registration_fee' => $this->input->post('registration_fee'),
            'registration_chips' => $this->input->post('registration_chips'),
            'winning_amount' => $this->input->post('winning_amount'),
            'name' => $this->input->post('name'),
            'max_player' => $this->input->post('max_player'),
            'total_round' => $this->input->post('total_round'),
            'tournament_type_id' => $this->input->post('tournament_type_id'),
            'is_mega_tournament' => $this->input->post('is_mega_tournament'),
            'is_winner_get_pass' => $this->input->post('is_winner_get_pass'),
            'pass_of_tournament_id' => $this->input->post('pass_of_tournament_id'),
            'total_pass_count' => $this->input->post('total_pass_count'),
            'added_date' => date('Y-m-d H:i:s')
        ];
        $TableMaster = $this->TournamentMaster_model->AddTournamentMaster($data);
        if ($TableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Tournament Master Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/TournamentMaster');
    }

    public function update()
    {
        $data = [
            'registration_start_date' => $this->input->post('registration_start_date'),
            'registration_start_time' => $this->input->post('registration_start_time'),
            'start_date' => $this->input->post('start_date'),
            'start_time' => $this->input->post('start_time'),
            'max_entry_pass' => $this->input->post('max_entry_pass'),
            'registration_fee' => $this->input->post('registration_fee'),
            'registration_chips' => $this->input->post('registration_chips'),
            'winning_amount' => $this->input->post('winning_amount'),
            'name' => $this->input->post('name'),
            'max_player' => $this->input->post('max_player'),
            'total_round' => $this->input->post('total_round'),
            'tournament_type_id' => $this->input->post('tournament_type_id'),
            'is_mega_tournament' => $this->input->post('is_mega_tournament'),
            'is_winner_get_pass' => $this->input->post('is_winner_get_pass'),
            'pass_of_tournament_id' => $this->input->post('pass_of_tournament_id'),
            'total_pass_count' => $this->input->post('total_pass_count'),
        ];
        $TableMaster = $this->TournamentMaster_model->UpdateTournamentMaster($data, $this->input->post('id'));
        if ($TableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Teen Patti Table Master Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/TournamentMaster');
    }
    /// Prize Master
    public function prize($id = '')
    {
        // print_r($Tournamentid);exit;
        $data = [
            'title' => 'Prize Master',
            'AllPrizeMaster' => $this->TournamentMaster_model->AllPrizeMasterList($id),
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/TournamentMaster/addPrize/' . $id, 'Add Prize Master'];
        }
        template('tournament_master_prize/index', $data);
    }
    public function addPrize($Tournamentid = '')
    {
        $data = [
            'title' => 'Add Prize Master',
            'tournament_id' => $Tournamentid,
        ];

        template('tournament_master_prize/add', $data);
    }
    public function editPrize($id)
    {
        // print_r($id);
        // exit();
        $data = [
            'title' => 'Edit Prize Master',
            'PrizeMaster' => $this->TournamentMaster_model->ViewPrizeMaster($id)
        ];

        template('tournament_master_prize/edit', $data);
    }
    public function insertPrize()
    {

        $data = [
            'tournament_id' => $this->input->post('tournament_id'),
            'from_position' => $this->input->post('from_position'),
            'to_position' => $this->input->post('to_position'),
            'players' => $this->input->post('players'),
            'winning_price' => $this->input->post('winning_price'),
            'given_in_round' => $this->input->post('given_in_round'),
            'added_date' => date('Y-m-d H:i:s')
        ];

        // print_r($data);
        // exit();
        $TableMaster = $this->TournamentMaster_model->AddPrizeMaster($data);
        if ($TableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Prize Master Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/tournamentMaster/prize/' . $this->input->post('tournament_id'));
    }
    public function updatePrize()
    {
        // print_r($id);
        // exit();
        $id = $this->input->post('id');
        $data = [
            // 'tournament_id' => $this->input->post('tournament_id'),
            'from_position' => $this->input->post('from_position'),
            'to_position' => $this->input->post('to_position'),
            'players' => $this->input->post('players'),
            'winning_price' => $this->input->post('winning_price'),
            'given_in_round' => $this->input->post('given_in_round'),
            // 'added_date' => date('Y-m-d H:i:s')
        ];
        // print_r($id);
        // exit();
        $TableMaster = $this->TournamentMaster_model->UpdatePrizeMaster($data, $id);
        if ($TableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Prize Master Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/TournamentMaster/prize');
    }
    public function deletePrize($id)
    {
        if ($this->TournamentMaster_model->DeletePrize($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Prize Master Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/TournamentMaster/prize');
    }

    // Round Master
    public function round($id = '')
    {
        $data = [
            'title' => 'Round Master',
            'AllRoundMaster' => $this->TournamentMaster_model->AllRoundMasterList($id),
        ];
        $tournamentDetails = $this->TournamentMaster_model->ViewTournamentMaster($id);
        if ($_ENV['ENVIRONMENT'] != 'demo' && $tournamentDetails->total_round > count($data['AllRoundMaster'])) {
            $data['SideBarbutton'] = ['backend/TournamentMaster/addRound/' . $id, 'Add Round Master'];
        }

        template('tournament_master_round/index', $data);
    }
    public function addRound($Tournamentid = '')
    {
        $totalRound = $this->TournamentMaster_model->getTournamentRoundCount($Tournamentid);
        $tournamentDetails = $this->TournamentMaster_model->ViewTournamentMaster($Tournamentid);
        $data = [
            'title' => 'Add Round Master',
            'tournament_id' => $Tournamentid,
            'tournament_round' => $totalRound + 1,
            'tournamentDetails' => $tournamentDetails
        ];

        template('tournament_master_round/add', $data);
    }
    // Method to get the latest round number
    /*public function get_latest_round()
    {
        $latest_round = $this->TournamentMaster_model->get_latest_round();
        echo json_encode(['latest_round' => $latest_round]);
    }*/
    public function editRound($id)
    {
        // print_r($id);
        // exit();
        $data = [
            'title' => 'Edit Round Master',
            'RoundMaster' => $this->TournamentMaster_model->ViewRoundMaster($id)
        ];

        template('tournament_master_round/edit', $data);
    }
    public function insertRound()
    {

        $round = $this->input->post('round');  // Ensure 'round' is correctly captured

        if ($round == 0 || empty($round)) {
            echo "Invalid round value!";
            return;
        }

        $data = [
            'tournament_id' => $this->input->post('tournament_id'),
            'round' => $round,
            'winner_user_count' => $this->input->post('winner_user_count'),
            'table_players_info' => $this->input->post('table_players_info'),
            'deal_info' => $this->input->post('deal_info'),
            'added_date' => date('Y-m-d H:i:s'),
            'start_date' => $this->input->post('start_date'),
            'start_time' => $this->input->post('start_time'),
        ];
        // echo "<pre>";
        // print_r($data);
        // exit();
        $TableMaster = $this->TournamentMaster_model->AddRoundMaster($data);
        if ($TableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Round Master Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/tournamentMaster/round/' . $this->input->post('tournament_id'));
    }
    public function updateRound()
    {

        $id = $this->input->post('id');
        $data = [
            'tournament_id' => $this->input->post('tournament_id'),
            'round' => $this->input->post('round'),
            'winner_user_count' => $this->input->post('winner_user_count'),
            'table_players_info' => $this->input->post('table_players_info'),
            'deal_info' => $this->input->post('deal_info'),
            'start_date' => $this->input->post('start_date'),
            'start_time' => $this->input->post('start_time'),
            // 'added_date' => date('Y-m-d H:i:s'),
        ];
        // print_r($data['status']);
        // exit();
        $TableMaster = $this->TournamentMaster_model->UpdateRoundMaster($data, $id);
        if ($TableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Round Master Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/tournamentMaster/round/' . $this->input->post('tournament_id'));
    }
    public function deleteRound($id)
    {
        if ($this->TournamentMaster_model->DeleteRound($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Round Master Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect($_SERVER['HTTP_REFERER']);
        // redirect('backend/tournamentMaster/round/' . $this->input->post('tournament_id'));
    }
}
