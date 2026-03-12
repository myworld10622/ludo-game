<?php

function send_email($to, $subject, $view, $data = '') {
    $ci = & get_instance();
    $ci->load->library('email');
    $result = $ci->email
            ->from('support@androappstech.com', PROJECT_NAME)
            ->to($to)
            ->subject($subject)
            ->message($ci->load->view('emails/' . $view, $data, true))
            ->set_mailtype('html')
            ->send();
            // if(!$result){
            //     echo 'Message could not be sent.';
            //     echo 'Mailer Error: ' . $ci->email->print_debugger(); die;
            // }
    return $result;
}
 