<?php

class Chats extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Chat_model','Users_model','Setting_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Chat Management',
            'AllChat' => $this->Chat_model->AllUserChatList()
        ];
        // echo "<pre>";
        // print_r($data["AllChat"]);
        // die;
        template('agent/chat', $data);
    }

    public function Start($id)
    {
        $id=$this->url_encrypt->decode($id);
        $data = [
            'title' => '',
            'chats'=>$this->Chat_model->AllChatById($id)
        ];
        template('agent/msg', $data);
    }

    public function getOldMsg()
    {
        
        $id=$this->url_encrypt->decode($this->input->post('id'));
        $chats=$this->Chat_model->AllChatById($id);
        $this->Chat_model->updateConversation(["seen" => 1], $id);
        $logo = $this->Setting_model->Setting()->logo;
        $userId = $id=$this->url_encrypt->decode($this->input->post('user_id'));
        $html='';
        foreach ($chats as $key => $value) {
                if($value->sender_type=='user'){
                                                    
                    $html.='<li class="clearfix">
                    <div class="chat-avatar">
                        <img src="'.base_url('data/post/'.$value->profile_pic).'" class="avatar-xs rounded-circle-msg" alt="male">
                        <span class="time">'.date('H:i',strtotime($value->added_date)).'</span>
                    </div>
                    <div class="conversation-text">
                        <div class="ctext-wrap">
                            <span class="user-name">'.$value->first_name.'</span>
                            <p>
                            '.$value->message.'
                            </p>
                        </div>
                    </div>
                </li>';
                }else{ 
                $html.='<li class="clearfix odd">
                    <div class="chat-avatar">
                        <img src="'.base_url('uploads/logo/'.$logo).'" class="avatar-xs rounded-circle-msg" alt="Female">
                        <span class="time">'.date('H:i',strtotime($value->added_date)).'</span>
                    </div>
                    <div class="conversation-text">
                        <div class="ctext-wrap">
                            <span class="user-name">'.$this->session->first_name.'</span>
                            <p>
                            '.$value->message.'
                            </p>
                        </div>
                    </div>
                </li>';
            } }


            $userhtml = "<h5 class='mt-4'>Users</h5><div class='mt-4'>";
            $users = $this->Chat_model->AllUserChatList();
            // echo "<pre>";print_r($users);die;
            foreach ($users as $key => $value) {
                if($value->tbl_user_id == $userId) {
                    $userhtml .= '<a class="chat-mail d-flex active-chat"';
                }else {
                    $userhtml .= '<a class="chat-mail d-flex"';
                }
                // $userhtml .= '<a class="chat-mail d-flex" 
                $userhtml .= 'href="#" 
                id="' . $this->url_encrypt->encode($value->id) . '" 
                onclick="getOldMsg(\'' . $this->url_encrypt->encode($value->id) . '\', \'' . $this->url_encrypt->encode($value->user_id) . '\', \'' . $value->first_name . '\', `scroll`, this)" 
                userid="' . $this->url_encrypt->encode($value->user_id) . '">
                <div class="flex-shrink-0 image-div me-3">
                    <img alt="Generic placeholder image" class="rounded-circle" height="36" src="' . base_url('data/post/' . $value->profile_pic) . '">
                </div>
                <div class="chat-user-box flex-grow-1">
                    <p class="m-0 user-title">' . $value->first_name . '</p>
                    <p class="text-muted" style="margin-bottom:0">' . $value->mobile . '</p>';
                    if($value->seen == 0) {
                        $userhtml .= '<div class="new-message"></div>';
                    }
                    $userhtml .= '</div></a>';
            }

            $userhtml .= "</div>";

            $response = [
                "chats" => $html,
                "userlist" => $userhtml
            ];
            echo json_encode($response);
    }

    public function sendMsg()
    {
        
        $id=$this->url_encrypt->decode($this->input->post('id'));
        $user_id=$this->url_encrypt->decode($this->input->post('user_id'));
        $msg=$this->input->post('msg');
        $data = [
            'sender_id' => $this->session->admin_id,
            'sender_type' =>'agent',
            'message' => $msg,
            'conversation_id' =>$id,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->Users_model->sendMsg($data);

        $UserData = $this->Users_model->UserProfile($user_id);
        $fcm_token = $UserData[0]->fcm;
        if(!empty($fcm_token)) {
            $title="New Agent Message";
            $body=$msg;
             $msg = array
             (
                 'title' => $title,
                 'body' => $body,
             );
             push_notification_android($fcm_token,$msg);
        }

        $convPayload = ["updated_date" => date('Y-m-d H:i:s')];
        $this->Chat_model->updateConversation($convPayload, $id);

        $chats=$this->Chat_model->AllChatById($id);
        $html='';

        $logo = $this->Setting_model->Setting()->logo;
        
        foreach ($chats as $key => $value) {
                if($value->sender_type=='user'){
                    
                    $html.='<li class="clearfix">
                    <div class="chat-avatar">
                        <img src="'.base_url('data/post/'.$value->profile_pic).'" class="avatar-xs rounded-circle-msg" alt="male">
                        <span class="time">'.date('H:i',strtotime($value->added_date)).'</span>
                    </div>
                    <div class="conversation-text">
                        <div class="ctext-wrap">
                            <span class="user-name">'.$value->first_name.'</span>
                            <p>
                            '.$value->message.'
                            </p>
                        </div>
                    </div>
                </li>';
                }else{ 
                $html.='<li class="clearfix odd">
                    <div class="chat-avatar">
                        <img src="'.base_url('uploads/logo/'.$logo).'" class="avatar-xs rounded-circle-msg" alt="Female">
                        <span class="time">'.date('H:i',strtotime($value->added_date)).'</span>
                    </div>
                    <div class="conversation-text">
                        <div class="ctext-wrap">
                            <span class="user-name">'.$this->session->first_name.'</span>
                            <p>
                            '.$value->message.'
                            </p>
                        </div>
                    </div>
                </li>';
            } }
            // echo $html;

            $userhtml = "<h5 class='mt-4'>Users</h5><div class='mt-4'>";
            $users = $this->Chat_model->AllUserChatList();
            foreach ($users as $key => $value) {
                // $userhtml .= '<a class="chat-mail d-flex" 
                if($value->tbl_user_id == $user_id) {
                    $userhtml .= '<a class="chat-mail d-flex active-chat"';
                }else {
                    $userhtml .= '<a class="chat-mail d-flex"';
                }
                // $userhtml .= '<a class="chat-mail d-flex" 
                $userhtml .= 'href="#"
                id="' . $this->url_encrypt->encode($value->id) . '" 
                onclick="getOldMsg(\'' . $this->url_encrypt->encode($value->id) . '\', \'' . $this->url_encrypt->encode($value->user_id) . '\', \'' . $value->first_name . '\', `scroll`, this)" 
                userid="' . $this->url_encrypt->encode($value->user_id) . '">
                <div class="flex-shrink-0 image-div me-3">
                    <img alt="Generic placeholder image" class="rounded-circle" height="36" src="' . base_url('data/post/' . $value->profile_pic) . '">
                </div>
                <div class="chat-user-box flex-grow-1">
                    <p class="m-0 user-title">' . $value->first_name . '</p>
                    <p class="text-muted" style="margin-bottom:0">' . $value->mobile . '</p>';
                    if($value->seen == 0) {
                        $userhtml .= '<div class="new-message"></div>';
                    }
                    $userhtml .= '</div></a>';
            }

            $userhtml .= "</div>";

            $response = [
                "chats" => $html,
                "userlist" => $userhtml
            ];
            echo json_encode($response);
    }
    public function Settings()
    {
        $data = [
            'title' => 'Setting Management',
            'setting' => $this->Chat_model->AgentDetails($this->session->admin_id),
        ];
        // $data['SideBarbutton'] = ['backend/Agent/add', 'Add Agent'];
        template('agent/setting', $data);
    }



    public function add()
    {
        $data = [
            'title' => 'Add Agent'
        ];

        template('agent/add', $data);
    }

    public function insert()
    {
       $email = $this->input->post('email');
       // Check if email already exists
       $email_exists = $this->Chat_model->checkEmailExists($email);

       if ($email_exists) {
       $this->session->set_flashdata('msg', array('message' => 'Email ID already exists', 'class' => 'error', 'position' => 'top-right'));
       redirect('backend/Agent/add');
       } else {
    // Email doesn't exist, proceed with adding agent
         $data = [
            'first_name' => $this->input->post('first_name'),
            'last_name' => $this->input->post('last_name'),
            'email_id' => $this->input->post('email'),
            'password' => $this->input->post('password'),
            'sw_password' => md5($this->input->post('password')),
            'mobile' => $this->input->post('mobile'),
            'role' => 2,
            'addedby' => $this->session->admin_id,
            'created_date' => date('Y-m-d H:i:s')
        ];
        $agent = $this->Chat_model->Addagent($data);
        if ($agent) {
            $this->session->set_flashdata('msg', array('message' => 'Agent Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Agent');
    }
}

    public function edit_Agent($id)
    {
        $data = [
            'title' => 'Edit Agent',
            'agent' => $this->Chat_model->AgentDetails($id)
        ];
        // echo '<pre>';print_r($data);die;
        template('agent/edit', $data);
    }

    public function edit_wallet($id)
    {
       $data = [
            'title' => 'Add Wallet Amount',
            'User' => $this->Chat_model->UserAgentProfile($id)
        ];
        // echo '<pre>';print_r($data);die;
        template('agent/agentadd_wallet', $data); 
    }

    public function deduct_wallet($id)
    {
        $data = [
            'title' => 'Deduct Wallet Amount',
            'User' => $this->Chat_model->UserAgentProfile($id)
        ];

        template('agent/deduct_wallet', $data);
    }

    public function update_wallet()
    {
        $user = $this->Chat_model->UpdateWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
        if ($user) {
            direct_admin_profit_statement(ADMIN_AGENT_TRANSFER,-$this->input->post('amount'),$this->input->post('user_id'));
            log_statement ($this->input->post('user_id'), ADMIN_AGENT_TRANSFER, $this->input->post('amount'),0,0,1);
            $user = $this->Chat_model->WalletLog($this->input->post('amount'), $this->input->post('user_id'));
            $this->session->set_flashdata('msg', array('message' => 'Agent Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Agent');
    }



    public function update_deduct_wallet()
    {
        
        $user = $this->Chat_model->DeductWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
        if ($user) {
            direct_admin_profit_statement(ADMIN_AGENT_TRANSFER,$this->input->post('amount'),$this->input->post('user_id'));
            log_statement ($this->input->post('user_id'), ADMIN_AGENT_TRANSFER, $this->input->post('amount'),0,0,1);
            $user = $this->Chat_model->WalletLog('-'.$this->input->post('amount'), $this->input->post('user_id'));
            $this->session->set_flashdata('msg', array('message' => 'Agent Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Agent');
    }


    public function setting_update()
    {
        $data = [
            'agent_withdraw_rate' => $this->input->post('agent_withdraw_rate'),
            'agent_deposite_rate' => $this->input->post('agent_deposite_rate'),
        ];
        // print_r($data);die;
        $agent = $this->Chat_model->Updateagent($this->session->admin_id, $data);
        if ($agent) {
            $this->session->set_flashdata('msg', array('message' => 'Setting Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/agent/Settings');
    }

    public function update_Agent()
    {
        $password = $this->input->post('password');
        $md5Password = md5($password); // Convert password to MD5 hash
        $data = [
            'first_name' => $this->input->post('first_name'),
            'last_name' => $this->input->post('last_name'),
            'password' => $password, // Store original password in 'password' column
            'sw_password' => $md5Password, // Store MD5 hashed password in 'sw_password' column
            'email_id' => $this->input->post('email_id'),
            'mobile' => $this->input->post('mobile'),
        ];
        // print_r($data);die;
        $agent = $this->Chat_model->Updateagent($this->input->post('agent_id'), $data);
        if ($agent) {
            $this->session->set_flashdata('msg', array('message' => 'Agent Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Agent');
    }

    public function delete($id)
    {
        if ($this->Chat_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Agent Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Agent');
    }
    public function view($user_id)
    {
        $data = [
            'title' => 'View Logs',     
            'AllWalletLog' => $this->Chat_model->View_WalletLog($user_id),
        ];
        // echo '<pre>';print_r($data);die;
        template('agent/view', $data);
    }

}