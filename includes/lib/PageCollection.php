<?php

/*
 * Created on December 26, 2008
 *
 * bispark software services
 * www.bispark.com
 */
class PageCollection {
	var $total_records, $total_pages;
	var $prev_from, $prev_page, $next_from, $next_page;
	var $first_page, $last_page;
	var $offset;
	var $action_url;
	
	function __construct($args, $tems_per_page, $total_records, $action_url) {
		$this->action_url = $action_url;
		$this->total_records = $total_records;
		$this->items_per_page = $tems_per_page;
		$this->total_pages = $this->items_per_page > 0 ? (ceil($this->total_records / $this->items_per_page)) : 0;
		$this->first_page = 0;
		
		$from = isset ($args['from']) ? $args['from'] : 0;
		$this->offset = $from;
		
				$page = $this->items_per_page* ($this->total_pages -1);
				
				$this->last_page = $page;
				if ($this->offset == $this->first_page) {
					$this->page_index = 1;
				} else
					if ($this->offset == $this->last_page) {
						$this->page_index = $this->total_pages;
					} else {
						$this->page_index = isset ($args['page_index']) ? $args['page_index'] : 0;
					}
				$this->prev_page = $this->page_index - 1;
				$this->next_page = $this->page_index + 1;
				
				$from = ($from >= 0 && $from < $this->total_records) ? $from : 0;
				$this->prev_from = $this->offset - $this->items_per_page;
				$this->next_from = $from + $this->items_per_page;

			
		
	}
	
	function getPreviousFrom() {
		return $this->prev_from;
	}
	function getPreviousPageNumber() {
		return $this->prev_page;
	}
	function getNextFrom() {
		return $this->next_from;
	}
	function getNextPageNumber() {
		return $this->next_page;
	}
	function getFirstPageNumber() {
		return $this->first_page;
	}
	function getLastPageNumber() {
		return $this->last_page;
	}
	
	
	function getIsDisablePagination() {
		return ($this->offset == 0 && $this->page_index == $this->total_pages) ? true : false;
	}
	function getIsDisableFirst() {
		
		return ($this->offset <= $this->first_page) ? true : false;
	}
	function getIsDisablePrev() {
		return ($this->prev_from < 0) ? true : false;
	}
	function getIsDisableNext() {
		return ($this->next_from > ($this->total_records -1)) ? true : false;
	}
	function getIsDisableLast() {
		return ($this->offset == $this->last_page) ? true : false;
	}
	function getPaginationBlock() {
		if(!$this->getIsDisablePagination()) {
			return " Showing page " . $this->page_index . " of " . $this->total_pages;
		}
		
	}
	function getDoHidePagination() {
		return ($this->items_per_page > $this->total_records ) ? true : false;
	}
	function url() {
		return $this->action_url;
	}
	
	
}
?>
