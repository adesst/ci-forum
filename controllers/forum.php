<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forum extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->library('tank_auth');
		$this->load->helper('bbcode');
		$this->load->helper('text');
	}

	public function index($offset = "")
	{
		$hdata['title'] = "Forum";
		$hdata['page_id'] = "10";
		$data['cat'] = 0;
		$this->load->model('forum_model');
		$this->load->library('pagination');
		$config['uri_segment'] = 2;
		if($offset) {
		$config['base_url'] = substr(current_url(), 0, strrpos(current_url(), '/'));;
		} else {
		$config['base_url'] = current_url();
		}
		
		$count = $this->db->query("SELECT COUNT(*) AS ile FROM ".$this->db->dbprefix."threads ")->result_array();
		$config['total_rows'] = $count[0]['ile'];
		$config['prev_link'] = '&laquo; Poprzednia';
		$config['next_link'] = 'Następna &raquo;';
		$config['display_pages'] = FALSE; 

		$this->pagination->initialize($config);
		$data['links'] = $this->pagination->create_links();

		$data['threads'] = $this->forum_model->getThreads(null, $offset);
		$data['tags'] = $this->forum_model->getAllTags();
		$this->load->view('header', $hdata);

		$this->load->view('forum/index', $data);
		$fdata['extraFooterContent'] = '<script src="'.base_url().'js/forum.js"></script>';
		$this->load->view('footer', $fdata);
	}

	public function thread($thread_id, $offset = "")
	{
		$hdata['title'] = "Forum";
		$hdata['page_id'] = "10";
		$this->load->model('forum_model');
		$data['tags'] = $this->forum_model->getAllTags();
		$this->load->library('pagination');
		$config['uri_segment'] = 4;
		if($offset) {
		$config['base_url'] =substr(current_url(), 0, strrpos(current_url(), '/'));;
		} else {
		$config['base_url'] = current_url();
		}
		
		$count = $this->db->query("SELECT COUNT(*) AS ile FROM ".$this->db->dbprefix."posts WHERE thread_id = '".$thread_id."' ")->result_array();
		$config['total_rows'] = $count[0]['ile'];

		$this->pagination->initialize($config);
		$data['links'] = $this->pagination->create_links();

		$this->load->view('header', $hdata);

		$this->form_validation->set_rules('content', 'treść wpisu', 'trim|required|xss_clean');
		$this->form_validation->set_error_delimiters('<span class="error">', '</span>');

		if ($this->form_validation->run() == FALSE) {
		$data['thread'] = $this->forum_model->getThread($thread_id);
		$data['cat'] = $data['thread'][0]->cat_id;
		$data['firstpost'] = $this->forum_model->getFirstPost($thread_id);
		$data['posts'] = $this->forum_model->getPosts($thread_id, $offset);
		unset($data['posts'][0]);
		$this->load->view('forum/thread', $data);
	} else {
		$addPost = $this->forum_model->addPost($thread_id);
		if ($addPost) {
		$message = 'Twój wpis został pomyślnie dodany!';
		} else {
		$message = 'Nie udało się dodać Twojego wpisu!';	
		}
		$this->session->set_flashdata('message', $message);
		redirect(current_url());
	}
		$fdata['extraFooterContent'] = '<script src="'.base_url().'js/forum.js"></script>';
		$this->load->view('footer', $fdata);
	}	

	public function create()
	{
		if (!$this->tank_auth->is_logged_in()) {
			$this->session->set_userdata('redirect', $this->uri->uri_string());
		redirect('/auth/login/');
		} else {
		$hdata['title'] = "Nowy wątek - Forum";
		$hdata['page_id'] = "10";
		$data['cat'] = 0;
		$this->load->model('forum_model');
		$this->load->view('header', $hdata);
		
		$this->form_validation->set_rules('subject', 'temat wpisu', 'trim|required|xss_clean');
		$this->form_validation->set_rules('content', 'treść wpisu', 'trim|required|xss_clean');
		$this->form_validation->set_rules('categories', 'kategoria', 'required|numeric');
		$this->form_validation->set_error_delimiters('<span class="error">', '</span>');

		if ($this->form_validation->run() == FALSE) {
		$data['tags'] = $this->forum_model->getAllTags();
		$this->load->view('forum/new', $data);
		} else {
		$addThread = $this->forum_model->addThread();
		if ($addThread) {
		$message = 'Twój wpis został pomyślnie dodany!';
		} else {
		$message = 'Nie udało się dodać Twojego wpisu!';
		}
		$this->session->set_flashdata('message', $message);
		redirect('forum');
		}
		$fdata['extraFooterContent'] = '<script src="'.base_url().'js/forum.js"></script>';
		$this->load->view('footer', $fdata);
	}
	}	

	public function post($post_id)
	{
		$hdata['title'] = "Forum";
		$hdata['page_id'] = "10";
		$this->load->model('forum_model');
		$data['tags'] = $this->forum_model->getAllTags();
		$this->load->view('header', $hdata);
		$data['post'] = $this->forum_model->getPost($post_id);
		$data['thread'] = $this->forum_model->getThread($data['post']->thread_id);
		$data['cat'] = $data['thread'][0]->cat_id;
		$this->load->view('forum/post', $data);
		$fdata['extraFooterContent'] = '<script src="'.base_url().'js/forum.js"></script>';
		$this->load->view('footer', $fdata);
	}	

	public function edit($post_id)
	{
		if (!$this->tank_auth->is_logged_in()) {
		redirect('/auth/login/');
		} else {
		$hdata['title'] = "Forum";
		$hdata['page_id'] = "10";
		$this->load->library('user_agent');
		if ($this->agent->is_referral()) {
    		$this->session->set_flashdata('ref', $this->agent->referrer()); 
    	} 
		$this->load->model('forum_model');
		if($this->forum_model->isEditable($post_id) || $this->tank_auth->is_superuser($this->tank_auth->get_user_id())) {
		$data['tags'] = $this->forum_model->getAllTags();
		$this->load->view('header', $hdata);
		$this->form_validation->set_rules('content', 'treść wpisu', 'trim|required|xss_clean');
		$this->form_validation->set_error_delimiters('<span class="error">', '</span>');

		if ($this->form_validation->run() == FALSE) {

		$data['post'] = $this->forum_model->getPost($post_id);
		$data['thread'] = $this->forum_model->getThread($data['post']->thread_id);
		$data['cat'] = $data['thread'][0]->cat_id;
		$this->load->view('forum/edit', $data);
	} else {
		$editPost = $this->forum_model->editPost($post_id);
		if ($editPost) {
		$message = 'Twój wpis został pomyślnie edytowany!';
		} else {
		$message = 'Nie udało się edytować Twojego wpisu!';
		}
		$this->session->set_flashdata('message', $message);
		if ($ref = $this->session->flashdata('ref')) {
			redirect($ref);
		} else {
			redirect('forum');
		}
		$fdata['extraFooterContent'] = '<script src="'.base_url().'js/forum.js"></script>';
		$this->load->view('footer', $fdata);
	}
			} else {
				redirect('forum');
			}
		}
	}	

	public function remove($post_id)
	{
	    if (!$this->tank_auth->is_logged_in()) {
		redirect('/auth/login/');
		} else {
		$this->load->model('forum_model');
		if($this->forum_model->isEditable($post_id) || $this->tank_auth->is_superuser($this->tank_auth->get_user_id())) {
		$this->load->library('user_agent');
		$thread_id = $this->forum_model->getThreadId($post_id);
		$removePost = $this->forum_model->removePost($post_id, $thread_id->thread_id);
		if ($removePost) {
		$message = 'Twój wpis został pomyślnie usunięty!';
		} else {
		$message = 'Nie udało się usunąć Twojego wpisu! Spróbuj później';
		}
		$this->session->set_flashdata('message', $message);
		if ($this->agent->is_referral()) {
			redirect($this->agent->referrer());
		} else {
			redirect('forum');
		}
		} else {
		redirect('forum');
		}
	}
	}

	public function delete($thread_id)
	{
	    if (!$this->tank_auth->is_logged_in()) {
		redirect('/auth/login/');
		} else {
		$this->load->model('forum_model');
		if($this->forum_model->isEditable($thread_id) || $this->tank_auth->is_superuser($this->tank_auth->get_user_id())) {
		$this->load->library('user_agent');
		$removeThread = $this->forum_model->removeThread($thread_id);
		if ($removeThread) {
		$message = 'Twój wątek został pomyślnie usunięty!';
		} else {
		$message = 'Nie udało się usunąć Twojego wątku! Spróbuj później';
		}
		$this->session->set_flashdata('message', $message);
		redirect('forum');
		} else {
		redirect('forum');
		}
	}
	}

	public function category($cat_id = 0, $offset = "")
	{
		$hdata['title'] = "Forum";
		$hdata['page_id'] = "10";
		$this->load->model('forum_model');
		$this->load->library('pagination');
		$config['uri_segment'] = 3;
		if($offset) {
		$config['base_url'] =substr(current_url(), 0, strrpos(current_url(), '/'));;
		} else {
		$config['base_url'] = current_url();
		}
		
		$count = $this->db->query("SELECT COUNT(*) AS ile FROM ".$this->db->dbprefix."threads WHERE 'cat_id'='".$cat_id."' ")->result_array();
		$config['total_rows'] = $count[0]['ile'];

		$this->pagination->initialize($config);
		$data['links'] = $this->pagination->create_links();

		$data['threads'] = $this->forum_model->getThreads($cat_id, $offset);
		$data['tags'] = $this->forum_model->getAllTags();
		$data['cat'] = $cat_id;
		$this->load->view('header', $hdata);

		$this->load->view('forum/index', $data);
		$fdata['extraFooterContent'] = '<script src="'.base_url().'js/forum.js"></script>';
		$this->load->view('footer', $fdata);
	}

	public function search($offset='')
	{
		if ($this->input->post('q')) {
		$hdata['title'] = "Forum";
		$hdata['page_id'] = "10";
		$this->load->model('forum_model');
		$data['term'] = $this->input->post('q', true);
		$this->load->library('pagination');
		$config['uri_segment'] = 2;
		$config['base_url'] = base_url('search');
		$count = $this->forum_model->count_search($data['term']);
		$config['total_rows'] = $count;
		$this->pagination->initialize($config);
		$data['links'] = $this->pagination->create_links();
		$this->load->view('header', $hdata);
		$data['tags'] = $this->forum_model->getAllTags();
 		$data['results'] = $this->forum_model->search($offset);
 		$data['cat'] = 0;
		$this->load->view('forum/search', $data);
		$fdata['extraFooterContent'] = '<script src="'.base_url().'js/forum.js"></script>';
		$this->load->view('footer', $fdata);
	} else {
	    redirect('forum');
	}
	}

}