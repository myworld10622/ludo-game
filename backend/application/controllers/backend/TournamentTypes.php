<?php
class TournamentTypes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['TournamentTypes_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Rummy Tournament Types',
            'AllTournamentTypes' => $this->TournamentTypes_model->AllTournamentTypesList()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/TournamentTypes/add', 'Add Tournament Types'];
        }
        template('tournament_types/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Tournament Types'
        ];

        template('tournament_types/add', $data);
    }

    public function insert()
    {
        $config['upload_path'] = './uploads/images/'; // Path where the image will be saved
        $config['allowed_types'] = 'gif|jpg|png|jpeg';  // Allowed file types
        $config['max_size'] = 2048;               // Maximum size in kilobytes

        $this->load->library('upload', $config);

        if ($this->upload->do_upload('image')) {
            // Get the uploaded file data
            $uploadData = $this->upload->data();
            $imageName = $uploadData['file_name']; // Retrieve the file name

            // Prepare data for database insertion
            $data = [
                'name' => $this->input->post('name'),
                'image' => $imageName, // Store the image name in the database
                'added_date' => date('Y-m-d H:i:s')
            ];

            // Save to database
            $MasterTypes = $this->TournamentTypes_model->AddTournamentTypes($data);

            if ($MasterTypes) {
                $this->session->set_flashdata('msg', array('message' => 'Tournament Master Added Successfully', 'class' => 'success', 'position' => 'top-right'));
            } else {
                $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
            }
        } else {
            // Handle file upload error
            $error = $this->upload->display_errors();
            $this->session->set_flashdata('msg', array('message' => $error, 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/TournamentTypes');
    }
    public function edit($id)
    {
        // print_r($id);
        // exit();
        $data = [
            'title' => 'Edit Tournament Types',
            'TournamentTypes' => $this->TournamentTypes_model->ViewTournamentTypes($id)
        ];

        template('tournament_types/edit', $data);
    }

    public function update()
    {
        $id = $this->input->post('id'); // Get the ID of the record
        $existingImage = $this->input->post('existing_image'); // Get the existing image name

        if (!empty($_FILES['image']['name'])) {
            // Handle file upload
            $config['upload_path'] = './uploads/images/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            $this->load->library('upload', $config);

            if ($this->upload->do_upload('image')) {
                $uploadData = $this->upload->data();
                $imageName = $uploadData['file_name']; // Use the new image if uploaded
            } else {
                $this->session->set_flashdata('msg', [
                    'message' => $this->upload->display_errors(),
                    'class' => 'error',
                    'position' => 'top-right'
                ]);
                redirect('backend/TournamentTypes');
                return;
            }
        } else {
            // No new image uploaded, retain the existing image
            $imageName = $existingImage;
        }

        // Prepare the data for update
        $data = [
            'name' => $this->input->post('name'),
            'image' => $imageName, // Use the new or existing image name
        ];

        // Perform the update
        if ($this->TournamentTypes_model->UpdateTournamentTypes($id, $data)) {
            $this->session->set_flashdata('msg', [
                'message' => 'Tournament Type Updated Successfully',
                'class' => 'success',
                'position' => 'top-right'
            ]);
        } else {
            $this->session->set_flashdata('msg', [
                'message' => 'Something Went Wrong',
                'class' => 'error',
                'position' => 'top-right'
            ]);
        }

        redirect('backend/TournamentTypes');
    }


    public function delete($id)
    {
        if ($this->TournamentTypes_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Tournamaent Types Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/TournamentTypes');
    }


}