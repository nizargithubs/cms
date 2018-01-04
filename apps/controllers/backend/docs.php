<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Docs extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $data['title'] = "Files Management";
        $data['page'] = "backend/docs";
        $this->load->view('backend/page', $data);
    }

}

/* End of file home.php */
/* Location: ./apps/controllers/home.php */