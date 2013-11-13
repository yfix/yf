<?php

/**
* Comments integration
*/
class yf_comments_integration {
	
	/**
	* For home page method
	*/
	function _for_home_page($NUM_NEWEST_COMMENTS = 4){
		list($comments, $titles, $user_names) = $this->_get_comments($NUM_NEWEST_COMMENTS);
		
		if(!empty($comments)){
			foreach ((array)$comments as $comment){
			
				$replace2 = array(
					'text'			=> nl2br(_cut_bb_codes(_prepare_html($comment['text']))),
					'title'			=> _prepare_html($titles[$comment['object_name'].$comment['object_id']]),
					'user_name'		=> $user_names[$comment['user_id']]['nick'],
					'add_date'		=> _format_date($comment['add_date'],'long'),
					'link'			=> module('comments')->COMMENT_LINKS[$comment['object_name']].$comment['object_id'].'#cid_'.$comment['id'],
					'where_comment'	=> $comment['object_name'],
					'user_link'		=> './?object=user_profile&action=show&id='.$comment['user_id'],

				);
				
				$item .= tpl()->parse('comments'.'/for_home_page_item', $replace2);
			}
		}
		
		$replace = array(
			'items'		=> $item,
		);
		
		return tpl()->parse('comments'.'/for_home_page_main', $replace);
	}
	
	function _for_user_profile($user_id, $MAX_SHOW_COMMENTS){
	
		list($comments, $titles, $user_names) = $this->_get_comments($MAX_SHOW_COMMENTS, $user_id);
		
		if(!empty($comments)){
			foreach ((array)$comments as $comment){
			
				$replace2 = array(
					'num'			=> ++$i,
					'text'			=> nl2br(_cut_bb_codes(_prepare_html($comment['text']))),
					'title'			=> _prepare_html($titles[$comment['object_name'].$comment['object_id']]),
					'created'		=> _format_date($comment['add_date'],'long'),
					'view_link'		=> module('comments')->COMMENT_LINKS[$comment['object_name']].$comment['object_id'].'#cid_'.$comment['id'],
					'where_comment'	=> $comment['object_name'],
					'user_link'		=> './?object=user_profile&action=show&id='.$comment['user_id'],

				);
				
				$item .= tpl()->parse('comments'.'/for_user_profile_item', $replace2);
			}
		}
		
		return $item;
	}
	
	function _get_comments($NUM_NEWEST_COMMENTS, $user_id = ''){
	
		foreach ((array)module('comments')->COMMENT_LINKS as $key => $value){
			$where .= 'object_name=''.$key.''';
			
			if($value !== end(module('comments')->COMMENT_LINKS)) {
				$where .= ' OR ';
			}
		}
		
		if(!empty($user_id)){
			$user = 'AND user_id='.$user_id;
		}
		
		$Q = db()->query('SELECT * FROM '.db('comments').' WHERE ('.$where.') AND active=1 '.$user.' ORDER BY add_date DESC LIMIT '.$NUM_NEWEST_COMMENTS);
		while ($A = db()->fetch_assoc($Q)) {
			$comments[$A['id']] = $A;
			$user_ids[$A['user_id']] = $A['user_id'];
			
			$A['object_name'] == 'news' ? $news_ids[$A['object_id']] = $A['object_id'] : '';
			$A['object_name'] == 'articles' ? $articles_ids[$A['object_id']] = $A['object_id'] : '';
			$A['object_name'] == 'blog' ? $blog_ids[$A['object_id']] = $A['object_id'] : '';
			$A['object_name'] == 'gallery' ? $gallery_ids[$A['object_id']] = $A['object_id'] : '';
		}
		
		$user_names = user($user_ids, array('nick'));
		
		if(!empty($articles_ids)){
			$Q = db()->query('SELECT id,title FROM '.db('articles_texts').' WHERE id IN('.implode(',',$articles_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$titles['articles'.$A['id']] = $A['title'];
			}
		}
		
		if(!empty($news_ids)){
			$Q = db()->query('SELECT id,title FROM '.db('news').' WHERE id IN('.implode(',',$news_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$titles['news'.$A['id']] = $A['title'];
			}
		}
		
		if(!empty($blog_ids)){
			$Q = db()->query('SELECT id,title FROM '.db('blog_posts').' WHERE id IN('.implode(',',$blog_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$titles['blog'.$A['id']] = $A['title'];
			}
		}
		
		if(!empty($gallery_ids)){
			$Q = db()->query('SELECT id,name FROM '.db('gallery_photos').' WHERE id IN('.implode(',',$gallery_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$titles['gallery'.$A['id']] = $A['name'] !== '' ? $A['name'] : t('No title');
			}
		}
		
		return array($comments, $titles, $user_names);
	
	}
	
	/**
	* Hook for the RSS module
	*/
	function _rss_general(){
		foreach ((array)module('comments')->COMMENT_LINKS as $key => $value){
			$where .= 'object_name="'.$key.'"';
			
			if($value !== end(module('comments')->COMMENT_LINKS)) {
				$where .= ' OR ';
			}
		}
		
		$Q = db()->query('SELECT * FROM '.db('comments').' WHERE ('.$where.') AND active=1 ORDER BY add_date DESC LIMIT '.module('comments')->NUM_RSS);
		while ($A = db()->fetch_assoc($Q)) {
			$comments[$A['id']] = $A;
			$user_ids[$A['user_id']] = $A['user_id'];
			
			$A['object_name'] == 'news' ? $news_ids[$A['object_id']] = $A['object_id'] : '';
			$A['object_name'] == 'articles' ? $articles_ids[$A['object_id']] = $A['object_id'] : '';
			$A['object_name'] == 'blog' ? $blog_ids[$A['object_id']] = $A['object_id'] : '';
			$A['object_name'] == 'gallery' ? $gallery_ids[$A['object_id']] = $A['object_id'] : '';
		}
		
		$user_names = user($user_ids,array('nick'));
		
		if(!empty($articles_ids)){
			$Q = db()->query('SELECT id,title FROM '.db('articles_texts').' WHERE id IN('.implode(',',$articles_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$titles['articles'.$A['id']] = $A['title'];
			}
		}
		
		if(!empty($news_ids)){
			$Q = db()->query('SELECT id,title FROM '.db('news').' WHERE id IN('.implode(',',$news_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$titles['news'.$A['id']] = $A['title'];
			}
		}
		
		if(!empty($blog_ids)){
			$Q = db()->query('SELECT id,title FROM '.db('blog_posts').' WHERE id IN('.implode(',',$blog_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$titles['blog'.$A['id']] = $A['title'];
			}
		}
		
		if(!empty($gallery_ids)){
			$Q = db()->query('SELECT id,name FROM '.db('gallery_photos').' WHERE id IN('.implode(',',$gallery_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$titles['gallery'.$A['id']] = $A['name'] !== '' ? $A['name'] : t('No title');
			}
		}

		if(!empty($comments)){
			foreach ((array)$comments as $comment){

				$data[] = array(
					'title'			=> _prepare_html(t('Comments in '). $comment['object_name'].' - '.$titles[$comment['object_name']. $comment['object_id']]),
					'link'			=> process_url(module('comments')->COMMENT_LINKS[$comment['object_name']].$comment['object_id'].'#cid_'.$comment['id']),
					'description'	=> nl2br(_cut_bb_codes(_prepare_html($comment['text']))),
					'date'			=> $comment['add_date'],
					'author'		=> $user_names[$comment['user_id']]['nick'],
					'source'		=> '',
				);
			}
		}
		
		return $data;
	}
}
