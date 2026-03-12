<?php
class UserCategory extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['UserCategory_model']);
    }

    // public function index()
    // {
    //     $data = [
    //         'title' => 'User Category',
    //         'AllUserCategories' => $this->UserCategory_model->AllTableMasterList()
    //     ];
    //     $data['SideBarbutton'] = ['backend/UserCategory/add', 'Add Category'];
    //     template('user_category/index', $data);
    // }

    public function index()
    {
        $startDate = $this->input->get('start_date'); // Get the start date from the form
        $endDate = $this->input->get('end_date'); // Get the end date from the form

        // Assuming you want to filter based on these dates, you can pass them to your model function
        $data = [
            'title' => 'User Category',
            'AllUserCategories' => $this->UserCategory_model->AllTableMasterList($startDate, $endDate)
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/UserCategory/add', 'Add Category'];
        }
        template('user_category/index', $data);
    }


    public function add()
    {
        $data = [
            'title' => 'Add Category'
        ];

        template('user_category/add', $data);
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Category',
            'UserCategory' => $this->UserCategory_model->ViewTableMaster($id)
        ];

        template('user_category/edit', $data);
    }

    public function delete($id)
    {
        if ($this->UserCategory_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Category Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/UserCategory');
    }

    public function insert()
    {
        $data = [
            'name' => $this->input->post('name'),
            'amount' => $this->input->post('amount'),
            'percentage' => $this->input->post('percentage'),
            'added_date' => date('Y-m-d H:i:s')
        ];
        $check = $this->UserCategory_model->CheckDuplicate($this->input->post('name'));
        if (empty($check)) {
            $category = $this->UserCategory_model->AddTableMaster($data);
            if ($category) {
                $this->session->set_flashdata('msg', array('message' => 'Category Added Successfully', 'class' => 'success', 'position' => 'top-right'));
            } else {
                $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
            }
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Category Already Exists', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/UserCategory');
    }

    public function update()
    {
        $data = [
            'name' => $this->input->post('name'),
            'amount' => $this->input->post('amount'),
            'percentage' => $this->input->post('percentage'),
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $Category = $this->UserCategory_model->UpdateTableMaster($data, $this->input->post('id'));
        if ($Category) {
            $this->session->set_flashdata('msg', array('message' => 'Category Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/UserCategory');
    }
}
