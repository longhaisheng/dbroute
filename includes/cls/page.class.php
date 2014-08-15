<?php
class cls_page{

	/** 当前页 */
	public $page = 1;

	/** 默认每页显示记录数 */
	public $page_size = 20;

	/** 上一页页码 */
	public $page_pre = 1;

	/** 下一页页码 */
	public $page_next = 1;

	/** 总页数 */
	public $total_page;

	/** 生成分页时链接的URL */
	public $http_url;

	/** 从$http_url中要替换的字符串 */
	public $page_url = '{page}';

	/** 分页中间的数组显示数量 */
	public $page_num = 5;

	/** 数据列表 */
	public $list=array();

	/** 总记录数 */
	public $total_count=0;

	public function setList($list){
		$this->list=$list;
	}

	public function getList(){
		return $this->list;
	}

	public function getTotalCount(){
		return $this->total_count;
	}
        
        /**
         *
         * @param type $url 分页链接的URL 
         * @param type $total_count 总记录数
         * @param type $pageNo 当前页
         * @param type $page_size 每页显示记录数
         */
	public function __construct($url,$total_count,$pageNo,$page_size = 4){
		$pageNo = $pageNo > 0 ? $pageNo : 0;
		$page_pre = $pageNo > 1 ? $pageNo-1 : 1;
		$total_page = ceil($total_count/$page_size);
		$page_next = $pageNo < $total_page ? $pageNo + 1 : $total_page;

		$this->page = $pageNo;
		$this->page_size = $page_size;
		$this->page_pre = $page_pre;
		$this->page_next = $page_next;
		$this->total_page = $total_page;
		$this->http_url = $url;
		$this->total_count=$total_count;
	}

	public function run(){
		if($this->total_page <= 1){
			return '';
		}
		$str = '';
		$str .= $this->setPre();
		$str .= $this->setNum();
		$str .= $this->setNext();
		return $str;
	}

	public function setPre(){
		$one_url = str_replace($this->page_url,1,$this->http_url);
		$pre_url = str_replace($this->page_url,$this->page_pre,$this->http_url);
		$str = '<li class="first"><a href="'.$one_url.'">首页</a></li>
        <li class="prev"><a href="'.$pre_url.'">上一页</a></li>';
		return $str;
	}

	public function setNext(){
		$next_url = str_replace($this->page_url,$this->page_next,$this->http_url);
		$wei_url = str_replace($this->page_url,$this->total_page,$this->http_url);
		$str = '<li class="next"><a href="'.$next_url.'">下一页</a></li>
          <li class="last"><a href="'.$wei_url.'">尾页</a></li>';
		return $str;
	}

	public function setNum(){
		if($this->total_page <= 5){
			$start_page = 1;
			$end_page = $this->total_page;
		}else{
			$start_page = ($this->page - 2 > 0) ? $this->page - 2 : 1;
			$end_page = ($start_page + $this->page_num -1 < $this->total_page) ? $start_page + $this->page_num - 1 : $this->total_page;
		}
		$str = '';

		if($this->page >= 5 && $this->page<$this->total_page){//显示分页组件的最前面两页
			for($i=1;$i<=2;$i++){
				$http_url = str_replace($this->page_url,$i,$this->http_url);
				$str .= '<li><a href="'.$http_url.'"';
				if($this->page == $i){
					$str .= ' class="this"';
				};
				$str .= '>'.$i.'</a></li>';
			}
			if($this->page > 5){
				$str .= '<li><a class="dot">...</a></li>';
			}
		}

		for($i=$start_page;$i<=$end_page;$i++){//显示分页组件的中间页
			$http_url = str_replace($this->page_url,$i,$this->http_url);
			$str .= '<li><a href="'.$http_url.'"';
			if($this->page == $i){
				$str .= ' class="this"';
			};
			$str .= '>'.$i.'</a></li>';
		}

		if($this->total_page > 7 && $this->page<$this->total_page){//显示分页组件的最后面的一两页
			$page_start=0;
			$page_end=0;
			if($this->page+4<$this->total_page){
				$str .= '<li><a class="dot">...</a></li>';
				$page_start=$this->total_page-1;
				$page_end=$this->total_page;
			}
			if($this->page+4==$this->total_page){
				$page_start=$this->total_page-1;
				$page_end=$this->total_page;
			}
			if($this->page+3==$this->total_page){
				$page_start=$this->total_page;
				$page_end=$this->total_page;
			}

			if(!empty($page_start)&&!empty($page_end)){
				for($i=$page_start;$i<=$page_end;$i++){
					$http_url = str_replace($this->page_url,$i,$this->http_url);
					$str .= '<li><a href="'.$http_url.'"';
					if($this->page == $i){
						$str .= ' class="this"';
					};
					$str .= '>'.$i.'</a></li>';
				}
			}
		}
		return $str;
	}

}