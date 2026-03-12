<?php
class Country extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Country_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Country Management',
            'AllTableMaster' => $this->Country_model->AllTableMasterList()
        ];
        
        $data['SideBarbutton'] = ['backend/Country/add', 'Add'];
        template('country/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add'
        ];

        template('country/add', $data);
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit ',
            'TableMaster' => $this->Country_model->ViewTableMaster($id)
        ];

        template('country/edit', $data);
    }

    public function delete($id)
    {
        if ($this->Country_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Country');
    }

    // public function insert()
    // {
    //     $data = [
    //         'name' => $this->input->post('name'),
    //         'code' => $this->input->post('code'),
    //         'added_date' => date('Y-m-d H:i:s')
    //     ];
    //     $TableMaster = $this->Country_model->AddTableMaster($data);
    //     if ($TableMaster) {
    //         $this->session->set_flashdata('msg', array('message' => 'Added Successfully', 'class' => 'success', 'position' => 'top-right'));
    //     } else {
    //         $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
    //     }
    //     redirect('backend/Country');
    // }
    public function insert()
    {
        $this->load->library('upload');

        // File upload configuration
        $config['upload_path'] = 'uploads/images/'; // Adjust the path as needed
        $config['allowed_types'] = 'gif|jpg|jpeg|png';
        $config['max_size'] = 2048; // 2MB
        $config['encrypt_name'] = TRUE;

        $this->upload->initialize($config);

        if ($this->upload->do_upload('flag')) {
            $fileData = $this->upload->data();
            $flagImage = $fileData['file_name'];

            $data = [
                'name' => $this->input->post('name'),
                'code' => $this->input->post('code'),
                'image' => $flagImage,
                'added_date' => date('Y-m-d H:i:s')
            ];

            $TableMaster = $this->Country_model->AddTableMaster($data);

            if ($TableMaster) {
                $this->session->set_flashdata('msg', array('message' => 'Added Successfully', 'class' => 'success', 'position' => 'top-right'));
            } else {
                $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
            }
        } else {
            $this->session->set_flashdata('msg', array('message' => $this->upload->display_errors(), 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/Country');
    }


    // public function update()
    // {
    //     $data = [
    //         'name' => $this->input->post('name'),
    //         'code' => $this->input->post('code'),
    //         'updated_date' => date('Y-m-d H:i:s')
    //     ];
    //     $TableMaster = $this->Country_model->UpdateTableMaster($data, $this->input->post('id'));
    //     if ($TableMaster) {
    //         $this->session->set_flashdata('msg', array('message' => 'Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
    //     } else {
    //         $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
    //     }
    //     redirect('backend/Country');
    // }
    public function update()
{
    $this->load->library('upload');

    // File upload configuration
    $config['upload_path'] = 'uploads/images/'; // Adjust the path as needed
    $config['allowed_types'] = 'gif|jpg|jpeg|png';
    $config['max_size'] = 2048; // 2MB
    $config['encrypt_name'] = TRUE;

    $this->upload->initialize($config);

    $id = $this->input->post('id');
    $existingData = $this->Country_model->ViewTableMaster($id);

    if (!$existingData) {
        $this->session->set_flashdata('msg', array('message' => 'Invalid Country ID', 'class' => 'error', 'position' => 'top-right'));
        redirect('backend/Country');
        return;
    }

    $flagImage = $existingData->flag;

    if ($this->upload->do_upload('flag')) {
        $fileData = $this->upload->data();
        $flagImage = $fileData['file_name'];

        // Optionally, delete the old flag image file if needed
        // $oldFilePath = './uploads/flags/' . $existingData['flag'];
        // if (file_exists($oldFilePath)) {
        //     unlink($oldFilePath);
        // }
    }

    $data = [
        'name' => $this->input->post('name'),
        'code' => $this->input->post('code'),
        'image' => $flagImage,
        'updated_date' => date('Y-m-d H:i:s')
    ];

    $TableMaster = $this->Country_model->UpdateTableMaster($data, $id);

    if ($TableMaster) {
        $this->session->set_flashdata('msg', array('message' => 'Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
    } else {
        $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
    }

    redirect('backend/Country');
}

}
