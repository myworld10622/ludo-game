<?php
class LudoTableMaster_model extends MY_Model
{

    public function AllTableMasterList()
    {
        $this->db->from('tbl_ludo_table_master');
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }


    // public function getHistory()
    // {
    //     $this->db->select('tbl_ludo.*,tbl_users.name');
    //     $this->db->from('tbl_ludo');
    //     $this->db->join('tbl_users', 'tbl_users.id=tbl_ludo.winner_id');
    //     $this->db->order_by('tbl_ludo.id', 'DESC');
    //     $this->db->limit(10);
    //     $Query = $this->db->get();
    //     return $Query->result();
    // }


    public function ViewTableMaster($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_ludo_table_master');
        return $Query->row();
    }
    
    public function AddTableMaster($data)
    {
        $this->db->insert('tbl_ludo_table_master', $data);
        return $this->db->insert_id();
    }

    public function Delete($id)
    {
        $data = [
            'isDeleted' => TRUE,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_ludo_table_master', $data);
        return $this->db->last_query();
    }

    public function UpdateTableMaster($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_ludo_table_master', $data);
        return $this->db->last_query();
    }

    public function Gethistory($postData=null)
    {
        // print_r($_GET);die;
        $response = array();
        // echo '<pre>';print_r($response);die;
        ## Read value
        if (isset($postData)) {
        $draw = $postData['draw'];
        $min = $postData['min']?$postData['min']:date('Y-m-d');
        $max = $postData['max']?$postData['max']:date('Y-m-d');
            $start = $postData['start'];
            $rowperpage = $postData['length']; // Rows display per page
            $columnIndex = $postData['order'][0]['column']; // Column index
            $columnName = $postData['columns'][$columnIndex]['data']; // Column name
            $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
            $searchValue = $postData['search']['value']; // Search value

            ## Total number of records without filtering
            $this->db->select('tbl_ludo.*, tbl_users.name');
            $this->db->from('tbl_ludo');
            $this->db->join('tbl_users', 'tbl_users.id = tbl_ludo.winner_id', 'left');
            $this->db->where('tbl_ludo.winner_id!=', 0);
            $this->db->where('DATE(tbl_ludo.added_date) >=', $min);
            $this->db->where('DATE(tbl_ludo.added_date) <=', $max);
            // $this->db->join('tbl_ludo_log', 'tbl_ludo_log.game_id = tbl_ludo.id', 'left');
            // Add any necessary conditions or additional joins here
            $this->db->order_by('tbl_ludo.id', 'desc');
            $totalRecords = $this->db->get()->num_rows();

            $this->db->select('tbl_ludo.*, tbl_users.name');
            $this->db->from('tbl_ludo');
            $this->db->join('tbl_users', 'tbl_users.id = tbl_ludo.winner_id', 'left');
            $this->db->where('tbl_ludo.winner_id!=', 0);
            $this->db->where('DATE(tbl_ludo.added_date) >=', $min);
            $this->db->where('DATE(tbl_ludo.added_date) <=', $max);
            // $this->db->join('tbl_ludo_log', 'tbl_ludo_log.game_id = tbl_ludo.id', 'left');
            // Add any necessary conditions or additional joins here
            $this->db->order_by('tbl_ludo.id', 'desc');
            // $this->db->where($defaultWhere);
            if ($searchValue) {
                $this->db->group_start();
                $this->db->like('tbl_ludo.added_date', $searchValue, 'after');
                $this->db->like('tbl_users.name', $searchValue, 'after');
                $this->db->like('tbl_ludo.winner_id', $searchValue, 'after');
                // $this->db->like('tbl_ludo_log.game_id', $searchValue, 'after');
                $this->db->or_like('tbl_ludo.amount', $searchValue, 'after');
                $this->db->or_like('tbl_ludo.user_winning_amt', $searchValue, 'after');
                $this->db->or_like('tbl_ludo.admin_winning_amt', $searchValue, 'after');
                //$this->db->or_like('tbl_ludo.comission_amount', $searchValue, 'after');
            // $this->db->or_like('tbl_seven_up.email', $searchValue, 'after');
                //$this->db->or_like('tbl_user_category.name', $searchValue, 'after');
                //$this->db->or_like('tbl_seven_up.wallet', $searchValue, 'after');
                //$this->db->or_like('tbl_seven_up.added_date', $searchValue, 'after');
                $this->db->group_end();
            }

            $totalRecordwithFilter = $this->db->get()->num_rows();
            $this->db->select('tbl_ludo.*, tbl_users.name');
            $this->db->from('tbl_ludo');
            $this->db->join('tbl_users', 'tbl_users.id = tbl_ludo.winner_id', 'left');
            // $this->db->join('tbl_ludo_log', 'tbl_ludo_log.game_id = tbl_ludo.id', 'left');
            // $this->db->group_by('tbl_ludo_log.game_id');
            // Add any necessary conditions or additional joins here
            $this->db->order_by('tbl_ludo.id', 'desc');
            $this->db->where('tbl_ludo.winner_id!=', 0);
        // $this->db->order_by($columnName, $columnSortOrder);
            if ($searchValue) {
                $this->db->group_start();
                $this->db->like('tbl_ludo.added_date', $searchValue, 'after');
                $this->db->like('tbl_users.name', $searchValue, 'after');
                $this->db->like('tbl_ludo.winner_id', $searchValue, 'after');
                // $this->db->like('tbl_ludo_log.game_id', $searchValue, 'after');
                $this->db->or_like('tbl_ludo.amount', $searchValue, 'after');
                $this->db->or_like('tbl_ludo.user_winning_amt', $searchValue, 'after');
                $this->db->or_like('tbl_ludo.admin_winning_amt', $searchValue, 'after');
            // $this->db->or_like('tbl_ludo.comission_amount', $searchValue, 'after');
                //$this->db->or_like('tbl_user_category.name', $searchValue, 'after');
                //$this->db->or_like('tbl_seven_up.wallet', $searchValue, 'after');
                //$this->db->or_like('tbl_seven_up.added_date', $searchValue, 'after');
                $this->db->group_end();
            }
                $this->db->where('DATE(tbl_ludo.added_date) >=', $min);
                $this->db->where('DATE(tbl_ludo.added_date) <=', $max);
        
            $this->db->limit($rowperpage, $start);
            $records = $this->db->get()->result();
            $data = array();
            
            $i = $start+1;
            //echo '<pre>';print_r($records);die;
            foreach ($records as $record) {
            //     $status = '<select class="form-control" onchange="ChangeStatus('.$record->id.',this.value)">
            //     <option value="0"'.(($record->status == 0) ? 'selected' : '').'>Active</option>
            //     <option value="1" '.(($record->status == 1) ? 'selected' : '').'>Block</option>
            // </select>';
            //     $action = '<a href="'.base_url('backend/user/view/' . $record->id).'" class="btn btn-info"
            //     data-toggle="tooltip" data-placement="top" title="View Wins"><span
            //         class="fa fa-eye"></span></a>
            //         | <a href="'.base_url('backend/user/LadgerReports/' . $record->id).'" class="btn btn-info"
            //         data-toggle="tooltip" data-placement="top" title="View Ladger Report"><span class="ti-wallet"></span></a>
            // | <a href="'.base_url('backend/user/edit/' . $record->id).'" class="btn btn-info"
            //     data-toggle="tooltip" data-placement="top" title="Edit"><span
            //         class="fa fa-credit-card" ></span></a>

            // | <a href="'.base_url('backend/user/edit_wallet/' . $record->id).'" class="btn btn-info"
            //     data-toggle="tooltip" data-placement="top" title="Deduct Wallet"><span
            //         class="fa fa-credit-card" ></span></a>

            //         | <a href="'.base_url('backend/user/edit_user/' . $record->id).'" class="btn btn-info"
            //     data-toggle="tooltip" data-placement="top" title="Edit"><span
            //         class="fa fa-edit" ></span></a>
                    
                    
            // | <a href="'.base_url('backend/user/delete/' . $record->id).'" class="btn btn-danger"
            //     data-toggle="tooltip" data-placement="top" title="Delete" onclick="return confirm(\'Are You Sure Want To Delete '.$record->name.'?\')"><span
            //         class="fa fa-trash" ></span></a>';
                $data[] = array(
                "id"=>$i,
                "game_id"=>$record->id,
                "name"=>$record->name,
                "winner_id"=>$record->winner_id,
                "amount"=>$record->amount,
                "user_winning_amt"=>$record->user_winning_amt,
                "admin_winning_amt"=>$record->admin_winning_amt,
                //"comission_amount"=>$record->comission_amount,
                //"mobile"=>($record->mobile=='') ? $record->email : $record->mobile,
                //   "user_type"=>$record->user_type==1 ? 'BOT' : 'REAL',
                //   "user_category"=>$record->user_category,
                //   "wallet"=>$record->wallet,
                //   "winning_wallet"=>$record->winning_wallet,
                //"on_table"=>($record->table_id > 0) ? 'Yes' : 'No',
                // "status"=>$status,
                "added_date"=>date("d-m-Y h:i:s A", strtotime($record->added_date)),
                //"action"=>$action,
            );
                $i++;
            }
            
            ## Response
            $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data,
            );

            return $response;
        }
    }
}