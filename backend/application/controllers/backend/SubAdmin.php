<?php

class SubAdmin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['SubAdmin_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'SubAdmin Management',
            'AllSubAdmin' => $this->SubAdmin_model->AllSubAdminList()
        ];
        if ($_ENV['ENVIRONMENT']!= 'demo') {
        $data['SideBarbutton'] = ['backend/SubAdmin/add', 'Add Sub Admin'];
        }
        template('subadmin/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add SubAdmin'
        ];

        template('subadmin/add', $data);
    }


    public function insert()
    { $email = $this->input->post('email');
        // Check if email already exists
        $email_exists = $this->SubAdmin_model->checkEmailExists($email);
 
        if ($email_exists) {
        $this->session->set_flashdata('msg', array('message' => 'Email ID already exists', 'class' => 'error', 'position' => 'top-right'));
        redirect('backend/SubAdmin/add');
        } else {
        // Retrieve data from the form
        $data = [
            'first_name' => $this->input->post('first_name'),
            'last_name' => $this->input->post('last_name'),
            'email_id' => $this->input->post('email'),
            'password' => $this->input->post('password'),
            'sw_password' => md5($this->input->post('password')),
            'mobile' => $this->input->post('mobile'),
            'role' => 1,
            'created_date' => date('Y-m-d H:i:s')
        ];
 
        // Retrieve selected subadmins as an array
        $subadmins = $this->input->post('subadmin');
    
        // Check if $subadmins is an array before using it
        if (is_array($subadmins)) {
            // Convert array of subadmins into comma-separated string
            $subadminString = implode(',', $subadmins);
            


            // Add subadmins to the data array
            $data['subadmin'] = $subadminString;
    
            // Insert data into the database using the model method
            $subadmin = $this->SubAdmin_model->Addsubadmin($data);
    
            if ($subadmin) {
                $this->session->set_flashdata('msg', array('message' => 'Sub Admin Added Successfully', 'class' => 'success', 'position' => 'top-right'));
            } else {
                $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
            }
        } 
        else {
            // Handle the case where $subadmins is not an array
            // For example, set a flash message to notify the user
            $this->session->set_flashdata('msg', array('message' => 'Error: $subadmins is not an array', 'class' => 'error', 'position' => 'top-right'));
        }
    
       
        redirect('backend/SubAdmin');
    }
}
    

    public function edit_subadmin($id)
    {
        $subDomains = $this->SubAdmin_model->SubadminDetails($id);
        $subDomains->subadmin = explode(',', $subDomains->subadmin);
        $data = [
            'title' => 'Edit SubAdmin',
            'subadmin' => $subDomains
        ];

        // echo "<pre>";
        // print_r(($subDomains));die;
        template('subadmin/edit', $data);
    }


public function update_subadmin()
{
    // Retrieve subadmin ID from the form
    $id = $this->input->post('subadmin_id');

    // Prepare data to update
    $data = [
        'first_name' => $this->input->post('first_name'),
        'last_name' => $this->input->post('last_name'),
        'email_id' => $this->input->post('email'),
        'password' => $this->input->post('password'),
        'sw_password' => md5($this->input->post('password')), // Hash password
        'created_date' => date('Y-m-d H:i:s')
    ];


    $selected_subadmin = $this->input->post('subadmin');


     if (is_array($selected_subadmin)) {
    
     $subadmins_string = implode(',', $selected_subadmin);
     $data['subadmin'] = $subadmins_string;
     $subadmin_updated = $this->SubAdmin_model->Updatesubadmin($id, $data);

    if ($subadmin_updated) {
        $this->session->set_flashdata('msg', array('message' => 'Sub Admin Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
    } else {
        $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
    }
    } else {
    
    $this->session->set_flashdata('msg', array('message' => 'Error: No subadmins selected', 'class' => 'error', 'position' => 'top-right'));
    }

    // Redirect to the appropriate page
    redirect('backend/SubAdmin');

}

    public function delete($id)
    {
        if ($this->SubAdmin_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Sub Admin Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/SubAdmin');
    }

}