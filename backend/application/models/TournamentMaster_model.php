<?php
class TournamentMaster_model extends MY_Model
{

    public function AllTournamentMasterList()
    {
        $this->db->select('tbl_rummy_tournament_master.*, (SELECT COUNT(*) 
                FROM tbl_rummy_tournament_rounds 
                WHERE tbl_rummy_tournament_rounds.tournament_id = tbl_rummy_tournament_master.id) as round_count');
        $this->db->from('tbl_rummy_tournament_master');
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function ViewTournamentMaster($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_rummy_tournament_master');
        return $Query->row();
    }

    public function AddTournamentMaster($data)
    {
        $this->db->insert('tbl_rummy_tournament_master', $data);
        return $this->db->insert_id();
    }

    public function Delete($id)
    {
        $data = [
            'isDeleted' => TRUE,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_rummy_tournament_master', $data);
        return $this->db->last_query();
    }

    public function UpdateTournamentMaster($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_rummy_tournament_master', $data);
        return $this->db->last_query();
    }
    // Prize Master
    public function AllPrizeMasterList($id = '')
    {
        $this->db->from('tbl_rummy_tournament_prizes');
        $this->db->select('tbl_rummy_tournament_prizes.*,tbl_rummy_tournament_master.is_completed');
        $this->db->join('tbl_rummy_tournament_master', 'tbl_rummy_tournament_master.id=tbl_rummy_tournament_prizes.tournament_id');
        $this->db->where('tbl_rummy_tournament_prizes.isDeleted', false);
        if ($id) {
            $this->db->where('tournament_id', $id);
        }
        $this->db->order_by('tbl_rummy_tournament_prizes.id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function AddPrizeMaster($data)
    {
        $this->db->insert('tbl_rummy_tournament_prizes', $data);
        return $this->db->insert_id();
    }

    public function ViewPrizeMaster($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_rummy_tournament_prizes');
        return $Query->row();
    }
    public function UpdatePrizeMaster($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_rummy_tournament_prizes', $data);
        return $this->db->last_query();
    }
    public function DeletePrize($id)
    {
        $data = [
            'isDeleted' => TRUE,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_rummy_tournament_prizes', $data);
        return $this->db->last_query();
    }

    // Round Master

    public function AllRoundMasterList($id)
    {
        $this->db->from('tbl_rummy_tournament_rounds');
        $this->db->select('tbl_rummy_tournament_rounds.*,tbl_rummy_tournament_master.is_completed');
        $this->db->join('tbl_rummy_tournament_master', 'tbl_rummy_tournament_master.id=tbl_rummy_tournament_rounds.tournament_id');
        $this->db->where('tbl_rummy_tournament_rounds.isDeleted', false);
        $this->db->where('tournament_id', $id);
        $this->db->order_by('tbl_rummy_tournament_rounds.id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function AddRoundMaster($data)
    {
        $this->db->insert('tbl_rummy_tournament_rounds', $data);
        return $this->db->insert_id();
    }

    // Method to get the highest round number from the table
    public function getTournamentRoundCount($tournamentId)
    {
        // Fetch the latest round based on the highest ID from the table
        $this->db->select('tbl_rummy_tournament_rounds.round');
        // $this->db->from('tbl_rummy_tournament_rounds');
        $this->db->where('tournament_id', $tournamentId);
        $this->db->where('isDeleted', 0);

        return $this->db->get('tbl_rummy_tournament_rounds')->num_rows();

        // Check if the result is not empty and return the round value, otherwise return 0
        // return $result ? $result->round : 0;
    }


    public function ViewRoundMaster($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_rummy_tournament_rounds');
        return $Query->row();
    }
    public function UpdateRoundMaster($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_rummy_tournament_rounds', $data);
        return $this->db->last_query();
    }
    public function DeleteRound($id)
    {
        $data = [
            'isDeleted' => TRUE,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_rummy_tournament_rounds', $data);
        return $this->db->last_query();
    }
}
