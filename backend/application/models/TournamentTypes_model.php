<?php
class TournamentTypes_model extends MY_Model
{

    public function AllTournamentTypesList()
    {
        $this->db->from('tbl_rummy_tournament_types');
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function ViewTournamentTypes($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_rummy_tournament_types');
        return $Query->row();
    }

    public function AddTournamentTypes($data)
    {
        $this->db->insert('tbl_rummy_tournament_types', $data);
        return $this->db->insert_id();
    }

    public function Delete($id)
    {
        $data = [
            'isDeleted' => TRUE,
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_rummy_tournament_types', $data);
        return $this->db->last_query();
    }

    public function UpdateTournamentTypes($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbl_rummy_tournament_types', $data);
    }



}
