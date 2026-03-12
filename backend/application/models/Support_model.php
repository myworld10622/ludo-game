<?php

use phpDocumentor\Reflection\DocBlock\Tags\Return_;

class Support_model extends MY_Model
{
    public function Ticket_list($status, $startDate, $endDate, $category)
    {
        $this->db->select('tbl_tickets.*,tbl_users.name as user_name,tbl_users.mobile as user_mobile');
        $this->db->from('tbl_tickets');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_tickets.user_id');
        // filter
        if (!empty($startDate)) {
            $startDate = date('Y-m-d 00:00:00', strtotime($startDate));
            $endDate = date('Y-m-d 23:59:59', strtotime($endDate));
            $this->db->where('tbl_tickets.added_date >=', $startDate);
            $this->db->where('tbl_tickets.added_date <=', $endDate);
        } else {
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
            $this->db->where('tbl_tickets.added_date >=', $startDate);
            $this->db->where('tbl_tickets.added_date <=', $endDate);
        }
        $this->db->where('tbl_tickets.isDeleted', FALSE);
        $this->db->where('tbl_tickets.status', $status);
        if ($category == '1' || $category == '2' || $category == '3') {
            $this->db->where('tbl_tickets.category', $category);
        }
        // $this->db->where('tbl_tickets.category', $category);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function TicketCount($status=0)
    {
        $this->db->select('COUNT(id) as total');
        $this->db->from('tbl_tickets');
        $this->db->where('tbl_tickets.isDeleted', FALSE);
        if($status!=''){
            $this->db->where('tbl_tickets.status', $status);
        }
        $Query = $this->db->get();
        return $Query->row()->total;
    }

    public function ChangeStatus($id, $status)
    {
        $this->db->where('id', $id)
            ->set('status', $status)
            ->update('tbl_tickets');
        return $this->db->last_query();
    }


}