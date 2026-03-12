<?php
class ImageNotification extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ImageNotification_model');
        $this->load->model('Users_model');
    }

    public function index()
    {
        $data = [
            'title' => 'Welcome App Image',
            'AllNotification' => $this->ImageNotification_model->ImageList()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/ImageNotification/add', ' Add Image'];
        }
        template('image_notification/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Send Notification'
        ];

        template('image_notification/add', $data);
    }

    public function insert()
    {
        $inserted_id = 0;

        if (!empty($_FILES['image']['name'])) {
            $data = [
                'added_date' => date('Y-m-d H:i:s A')
            ];
            $data['image'] = upload_image($_FILES['image'], IMAGE_URL);
            $inserted_id = $this->ImageNotification_model->insert($data);
        }

        if ($inserted_id) {
            $this->session->set_flashdata('msg', array('message' => 'Image Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/ImageNotification');
    }

    public function delete($id)
    {
        if ($this->ImageNotification_model->update(array('isDeleted' => true), $id)) {
            $this->session->set_flashdata('msg', array('message' => 'Image Deleted Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/ImageNotification');
    }

}