<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Forum_model extends CI_Model
{

	function __construct()
	{
		parent::__construct();
	}

	function getThreads($cat_id, $offset)
	{
	$this->db->select('threads.*, users.username, user_profiles.*');
	$this->db->from('threads');
	$this->db->join('users', 'threads.user_id = users.id', 'left');
	$this->db->join('user_profiles', 'threads.user_id = user_profiles.user_id', 'left');

	if($cat_id) {
	$this->db->where('cat_id', $cat_id);
	}
	
	$this->db->order_by('thread_sticky', 'desc');
	$this->db->order_by('modified_on', 'desc');

    if(is_numeric($offset))
    {
    $this->db->limit(10, $offset);
    } else {
    $this->db->limit(10);
    }

	$query = $this->db->get();
	if ($query->num_rows()>0) return $query->result();
	return NULL;
	}

	function getThread($thread_id)
	{
	$this->db->select('threads.*, users.username, user_profiles.*');
	$this->db->from('threads');
	$this->db->join('users', 'threads.user_id = users.id', 'left');
	$this->db->join('user_profiles', 'threads.user_id = user_profiles.user_id', 'left');
	$this->db->where('thread_id', $thread_id);

	$query = $this->db->get();
	if ($query->num_rows()>0) return $query->result();
	return NULL;
	}

	function getThreadId($post_id)
	{
	$this->db->select('thread_id');
	$this->db->from('posts');
	$this->db->where('post_id', $post_id);

	$query = $this->db->get();
	if ($query->num_rows()==1) return $query->row();
	return NULL;
	}

	function getPosts($thread_id, $offset)
	{
	$this->db->select('posts.*, users.username, user_profiles.*');
	$this->db->from('posts');
	$this->db->join('users', 'posts.user_id = users.id', 'left');
	$this->db->join('user_profiles', 'posts.user_id = user_profiles.user_id', 'left');
	$this->db->where('thread_id', $thread_id);
	$this->db->order_by('post_id','asc');

    if(is_numeric($offset))
    {
    $this->db->limit(10, $offset-1);
    } else {
    $this->db->limit(10);
    }

	$query = $this->db->get();
	if ($query->num_rows()>0) return $query->result();
	return NULL;
	}

	function getPost($post_id)
	{
	$this->db->select('posts.*, users.username, user_profiles.*');
	$this->db->from('posts');
	$this->db->join('users', 'posts.user_id = users.id', 'left');
	$this->db->join('user_profiles', 'posts.user_id = user_profiles.user_id', 'left');
	$this->db->where('post_id', $post_id);

	$query = $this->db->get();
	if ($query->num_rows()==1) return $query->row();
	return NULL;
	}

	function getFirstPost($thread_id)
	{
	$this->db->select('posts.*, users.username, user_profiles.*');
	$this->db->select_min('post_id');
	$this->db->from('posts');
	$this->db->join('users', 'posts.user_id = users.id', 'left');
	$this->db->join('user_profiles', 'posts.user_id = user_profiles.user_id', 'left');
	$this->db->where('thread_id', $thread_id);
	$this->db->where('main_post', 1);

	$query = $this->db->get();
	if ($query->num_rows()==1) return $query->row();
	return NULL;
	}

	function addThread()
	{
	$subject = $this->input->post('subject');
	$user_id =  $this->tank_auth->get_user_id();
	$content = $this->input->post('content', TRUE);
	$category = $this->input->post('categories');

	$data = array(
                'thread_subject' => $subject,
                'user_id' => $user_id,
                'cat_id' => $category,
                'modified_on' => time(),
                'created_on' => time()
    		        );

    $this->db->insert('threads', $data); 
    $thread_id = $this->db->insert_id();

	$data2 = array(
				'thread_id' => $thread_id,
				'main_post' => '1',
                'content' => $content,
                'user_id' => $user_id,
                'date' => time()
    		        );
    $this->db->insert('posts', $data2); 

    return true;

	}

	function addPost($thread_id)
	{
	$user_id = $this->tank_auth->get_user_id();
	$content = $this->input->post('content', TRUE);

	$data = array(
				'thread_id' => $thread_id,
                'content' => $content,
                'user_id' => $user_id,
                'date' => time()
    		        );
    $this->db->insert('posts', $data);

    $this->db->like('thread_id', $thread_id);
	$this->db->from('posts');
	$count = $this->db->count_all_results()-1;

	$data2 = array('posts' => $count,
				'modified_on' => time());
	$this->db->where('thread_id', $thread_id);
	$this->db->update('threads', $data2);

    return true;

	}

	function isEditable($post_id)
	{
		$user_id = $this->tank_auth->get_user_id();
		$this->db->join('users', 'posts.user_id = users.id', 'left');
		$this->db->where('user_id', $user_id);
      	$this->db->where('post_id', $post_id);
      	$this->db->limit(1);
      	$q = $this->db->get('posts');
       	if($q->num_rows() == 1) {
      	return true;
      	} else {
        return false;
      	}
	}

	function editPost($post_id) 
	{
	$content = $this->input->post('content', TRUE);
	$data = array(
                'content' => $content,
                'modified_on' => time()
    		        );
	$this->db->where('post_id', $post_id);
	$this->db->update('posts', $data);

	return true;
	}

	function removePost($post_id, $thread_id) 
	{
	$this->db->where('post_id', $post_id);
	$this->db->delete('posts');

	$this->db->where('thread_id', $thread_id);
	$this->db->from('posts');
	$count = $this->db->count_all_results()-1;

	$data = array('posts' => $count,
				'modified_on' => time());
	$this->db->where('thread_id', $thread_id);
	$this->db->update('threads', $data);

	return true;
	}

	function removeThread($thread_id)
	{
	$this->db->where('thread_id', $thread_id);
	$this->db->delete('threads');

	$this->db->where('thread_id', $thread_id);
	$this->db->delete('posts');

	return true;
	}

	function getAllTags()
	{
	$this->db->order_by('order_id', 'asc');

	$query = $this->db->get('tags');
	if ($query->num_rows()>0) return $query->result();
	return NULL;
	}

	function search($offset)
	{
		$term = $this->input->post('q', true);
		$this->db->from('posts');
		$this->db->join('threads','posts.thread_id=threads.thread_id','left');
		$this->db->join('users', 'posts.user_id = users.id', 'left');
		$this->db->join('user_profiles', 'posts.user_id = user_profiles.user_id', 'left');
		$this->db->where('MATCH (content) AGAINST ("'.$term.'")', null, false);
		$this->db->order_by('thread_sticky','desc');
		$this->db->order_by('post_id','desc');

	    if(is_numeric($offset))
	    {
	    $this->db->limit(10, $offset-1);
	    } else {
	    $this->db->limit(10);
	    }
        $query = $this->db->get();
        return $query->result();
	}

	function count_search($term)
	{
		$this->db->from('posts');
		$this->db->where('MATCH (content) AGAINST ("'.$term.'")', null, false);

        $query = $this->db->get();
        return $this->db->count_all_results();
	}

}