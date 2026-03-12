<?php
class Notification extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Notification_model');
        $this->load->model('Users_model');
    }

    public function index()
    {
        $data = [
            'title' => 'Notification',
            'AllNotification' => $this->Notification_model->List()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/Notification/add', 'Add Notification'];
        }
        template('notification/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Send Notification'
        ];

        template('notification/add', $data);
    }

    public function insert()
    {
        $config['upload_path'] = './uploads/images/';
        $config['allowed_types'] = 'jpg|png|jpeg';
        $config['max_size'] = 10240;

        $this->load->library('upload', $config);

        // Check if a file is uploaded
        if (!$this->upload->do_upload('image')) {
            $this->session->set_flashdata('msg', array(
                'message' => $this->upload->display_errors(),
                'class' => 'error',
                'position' => 'top-right'
            ));
            redirect('backend/Notification');
            return;
        }
        // File uploaded successfully, get upload data
        $upload_data = $this->upload->data();
        $image_path = $upload_data['file_name']; // Retrieve the file name
        $data = [
            'msg' => $this->input->post('msg'),
            'image' => $image_path,
            'url' => $this->input->post('url'),
            'added_date' => date('Y-m-d H:i:s A')
        ];

        $Noti = $this->Notification_model->Add($data);
        if ($Noti) {

            $data['title'] = PROJECT_NAME;
            $data['body'] = $this->input->post('msg');
            $data['action'] = 'notification';

            push_notification_to_topic('news', $data);

            $this->session->set_flashdata('msg', array('message' => 'Notification Sent Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Notification');
    }

    public function delete($id)
    {
        if ($this->Notification_model->update(array('isDeleted' => true), $id)) {
            $this->session->set_flashdata('msg', array('message' => 'Image Deleted Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Notification');
    }
}
