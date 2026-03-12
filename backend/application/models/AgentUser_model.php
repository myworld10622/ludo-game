<?php

class AgentUser_model extends MY_Model
{
    public function AddagentUser($data)
    {
        $this->db->insert('tbl_users', $data);
        return $this->db->insert_id();
    }


    public function AgentDetails($id)
    {
        $this->db->select('tbl_admin.*');
        $this->db->from('tbl_admin');
        $this->db->where('tbl_admin.isDeleted', false);
        $this->db->where('tbl_admin.id', $id);
        $query = $this->db->get();
        return $query->row();
    }

    public function UserAgentProfile($id)
    {
        $this->db->select('tbl_admin.*');
        $this->db->from('tbl_admin');
        $this->db->where('tbl_admin.id', $id);
        $this->db->where('tbl_admin.isDeleted', false);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }



    public function Delete($id)
    {
        $data = [
            'isDeleted' => TRUE,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_users', $data);
        return $this->db->last_query();
    }
    public function Update($UserId, $data)
    {
        $this->db->where('id', $UserId);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }
    public function AllAgentUserList($id)
    {
        $this->db->select('tbl_users.*');
        $this->db->from('tbl_users');
        $this->db->where('tbl_users.created_by', $id);
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by('tbl_users.id', 'asc');
        // $this->db->limit(10);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GetUsers($postData = null, $id = '', $role = '')
    {
        // print_r($postData);
        // die;
        $response = array();

        ## Read value
        $draw = $postData['draw'];
        $start = $postData['start'];
        $rowperpage = ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') ? 10 : $postData['length'];
        // Limiting to 10 records if environment is "demo"
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        // $this->db->join('tbl_admin', 'tbl_users.created_by=tbl_admin.id', 'LEFT');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by('tbl_users.id', 'desc');
        if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') {
            $this->db->limit(10);
        }
        // echo $this->db->last_query();
        // $totalRecords = $this->db->get()->num_rows();
        $totalRecords = $this->db->count_all_results();

        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        // $this->db->join('tbl_admin', 'tbl_users.created_by=tbl_admin.id', 'LEFT');
        $this->db->where('tbl_users.isDeleted', false);
        if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') {
            $this->db->limit(10);
        }
        $this->db->order_by('tbl_users.id', 'desc');
        // echo $this->db->last_query();

        // $this->db->where($defaultWhere);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_users.name', $searchValue, 'after');
            // $this->db->like('tbl_admin.first_name', $searchValue, 'after');
            $this->db->like('tbl_users.id', $searchValue, 'after');
            $this->db->like('tbl_users.profile_pic', $searchValue, 'after');
            $this->db->or_like('tbl_users.mobile', $searchValue, 'after');
            $this->db->or_like('tbl_users.bank_detail', $searchValue, 'after');
            $this->db->or_like('tbl_users.adhar_card', $searchValue, 'after');
            $this->db->or_like('tbl_users.upi', $searchValue, 'after');
            $this->db->or_like('tbl_users.email', $searchValue, 'after');
            $this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.wallet', $searchValue, 'after');
            $this->db->or_like('tbl_users.added_date', $searchValue, 'after');
            $this->db->group_end();
        }

        // $totalRecordwithFilter = $this->db->get()->num_rows();
        $totalRecordwithFilter = $this->db->count_all_results();

        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        // $this->db->join('tbl_admin', 'tbl_users.created_by=tbl_admin.id', 'LEFT');

        $this->db->where('tbl_users.isDeleted', false);
        $this->db->where('tbl_users.created_by', $id);

        $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_users.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.id', $searchValue, 'after');
            $this->db->or_like('tbl_users.profile_pic', $searchValue, 'after');
            $this->db->or_like('tbl_users.mobile', $searchValue, 'after');
            $this->db->or_like('tbl_users.bank_detail', $searchValue, 'after');
            $this->db->or_like('tbl_users.adhar_card', $searchValue, 'after');
            $this->db->or_like('tbl_users.upi', $searchValue, 'after');
            $this->db->or_like('tbl_users.email', $searchValue, 'after');
            $this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.wallet', $searchValue, 'after');
            $this->db->or_like('tbl_users.bind_device', $searchValue, 'after');
            $this->db->or_like('tbl_users.added_date', $searchValue, 'after');
            $this->db->group_end();
        }

        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
        $data = array();

        $i = $start + 1;
        // echo '<pre>';print_r($records);die;
        foreach ($records as $record) {
            $status = '<select class="form-control" onchange="ChangeStatus(' . $record->id . ',this.value)">
            <option value="0"' . (($record->status == 0) ? 'selected' : '') . '>Active</option>
            <option value="1" ' . (($record->status == 1) ? 'selected' : '') . '>Block</option>
        </select>';
            $bet_lock_status = '<select class="form-control" onchange="changeBetLockStatus(' . $record->id . ',this.value)">
            <option value="0"' . (($record->bet_lock_status == 0) ? 'selected' : '') . '>UnLock</option>
            <option value="1" ' . (($record->bet_lock_status == 1) ? 'selected' : '') . '>lock</option>
        </select>';
            $action = '<a href="' . base_url('backend/user/view/' . $record->id) . '" class="btn btn-info"
        data-toggle="tooltip" data-placement="top" title="View Wins"><span
            class="fa fa-eye"></span></a>
            | <a href="' . base_url('backend/user/LadgerReports/' . $record->id) . '" class="btn btn-info"
            data-toggle="tooltip" data-placement="top" title="View Ladger Report"><span class="ti-wallet"></span></a>
    | <a href="' . base_url('backend/user/edit/' . $record->id) . '" class="btn btn-info"
        data-toggle="tooltip" data-placement="top" title="Add Wallet"><span
            class="fa fa-credit-card" ></span></a>

    | <a href="' . base_url('backend/user/edit_wallet/' . $record->id) . '" class="btn btn-danger"
        data-toggle="tooltip" data-placement="top" title="Deduct Wallet"><span
            class="fa fa-credit-card" ></span></a>

            | <a href="' . base_url('backend/user/edit_user/' . $record->id) . '" class="btn btn-info"
        data-toggle="tooltip" data-placement="top" title="Edit"><span
            class="fa fa-edit" ></span></a>
            
            
    | <a href="' . base_url('backend/user/delete/' . $record->id) . '" class="btn btn-danger"
        data-toggle="tooltip" data-placement="top" title="Delete" onclick="return confirm(\'Are You Sure Want To Delete ' . $record->name . '?\')"><span
            class="fa fa-trash" ></span></a>';
            $action .= $record->status ? '
 | <a href="' . base_url('backend/user/unblockUser/' . $record->id) . '" class="btn btn-info" onclick="return confirmunBlockUser();" data-toggle="tooltip" data-placement="top" title="Activate User">
    <span class="fa fa-check"></span>
</a>
' : '
| <a href="' . base_url('backend/user/blockUser/' . $record->id) . '" class="btn btn-danger" onclick="return confirmBlockUser();" data-toggle="tooltip" data-placement="top" title="Block User">
    <span class="fa fa-ban"></span> 
</a>
';

            $action .= $record->bet_lock_status ? '
| <a href="' . base_url('backend/user/unlockUser/' . $record->id) . '" class="btn btn-info" onclick="return confirmunBlockUser();" data-toggle="tooltip" data-placement="top" title="unLock Bet">
<span class="fa fa-lock-open"></span>
</a>
' : '
| <a href="' . base_url('backend/user/lockUser/' . $record->id) . '" class="btn btn-danger" onclick="return confirmBlockUser();" data-toggle="tooltip" data-placement="top" title="lock Bet">
<span class="fa fa-lock"></span> 
</a>
';
            $data[] = array(
                "id" => $i,
                "name" => $record->name,
                "mobile" => ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') ? "9892552468" : $record->mobile,
                "ID" => $record->id,
                "profile_pic" => '<img src="' . base_url('data/post/' . $record->profile_pic) . '" height="75px" width="75px" >',
                "bank_detail" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->bank_detail : "",
                "adhar_card" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->adhar_card : "",
                "upi" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->upi : "",
                "email" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->email : "",
                "user_type" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? ($record->user_type == 1 ? 'BOT' : 'REAL') : "",
                "user_category" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->user_category : "",
                "wallet" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->wallet : "",
                "winning_wallet" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->winning_wallet : "",
                "on_table" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? (($record->table_id > 0 || $record->rummy_table_id > 0 || $record->ander_bahar_room_id > 0 || $record->dragon_tiger_room_id > 0 || $record->seven_up_room_id > 0 || $record->jhandi_munda_id > 0) ? 'Yes' : 'No') : "",
                "status" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $status : "",
                "added_date" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? date("d-m-Y h:i:s A", strtotime($record->added_date)) : "",
                "action" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $action : "",
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

    public function UpdateWalletOrder($amount, $user_id, $bonus = 0)
    {
        $this->db->set('wallet', 'wallet+' . $amount, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        return true;
    }

    public function DeductWalletOrder($amount, $user_id, $bonus = 0)
    {
        $this->db->set('wallet', 'wallet-' . $amount, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_admin');

        return true;
    }

    public function WalletLog($amount, $user_id)
    {
        $data = [
            'user_id' => $user_id,
            'coin' => $amount,
            //'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_wallet_log', $data);
        return $this->db->insert_id();
    }

    public function isMobileNumberExists($mobile_number)
    {
        // Query to check if the mobile number exists in the database
        $this->db->where('mobile', $mobile_number);
        $this->db->where('isDeleted', 0);
        $query = $this->db->get('tbl_users');

        // Check if any row is returned
        return $query->num_rows();
    }

    public function Setting()
    {
        $this->db->from('tbl_admin');
        $this->db->where('isDeleted', false);

        $query = $this->db->get();
        return $query->row();
    }

    public function UpdateReferralCode($user_id, $referralId)
    {
        if (!$referralId) {
            $referralId = 'TEENPATTI';
        }
        $this->db->set('referral_code', $referralId . $user_id);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');
    }


}