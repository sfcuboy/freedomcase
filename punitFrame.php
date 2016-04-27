<?php

require './vendor/rmccue/requests/library/Requests.php';

class punitFrame {

	private $_url;
	private $_config;
	private $_request;
	public 	$body;
	public  $showDetail;

	public function __construct($configFile = '', $showDetail = false){
		$this->showDetail = $showDetail;
		if($configFile && file_exists($configFile) ){
			$config = parse_ini_file($configFile, true);
		}else{
			die("case ini file ".$configFile." error!");
		}

		if(empty($config)){
			die("read from ini file ".$configFile. " failure!\n");
		}

		$config['method']['method'] = strtolower($config['method']['method']);
		$this->_config = $config;
		$this->_url = $config['url']['url'];
		Requests::register_autoloader();
		$this->_request = $this->send();
	}

	public function send(){
		if('get' == $this->_config['method']['method'] && $this->_config['field']){
			$this->_url.='?'.http_build_query($this->_config['field']);
		}
		$request = Requests::request($this->_url, $this->_config['headers'], $this->_config['field'], $this->_config['method']['method']);
		if(200 == $request->status_code){
			return $request;
		}else{
			die("http request failure with code ".$request->status_code);	
		}

		return false;

	}

	public function getBodyOrigin(){
		return $this->_request->body;
	}

	public function parse(){
		if($this->_config['common']['parsetype'] == 'json_decode'){
			$this->body = json_decode($this->_request->body, true);
		}else{
			$this->body = $this->_request->body;
		}
	}

	public function getResult(){
		$ret = $this->filter();
		echo "\n",$this->_config['title']['title'], "  ========>  ", $ret ? "Pass" : "Failure","\n";
	}

	public function filter(){
		if($this->showDetail){
			echo "Request:[ ",$this->_config['url']['url']," ]\n\n";
			echo "Ret:",$this->_request->body,"\n\n";
		}
		$condition = isset($this->_config['condition']) ? $this->_config['condition'] : array();
		if($condition){
			$data = $this->body;

			foreach($condition as $key => $val){
				if(strpos($val, '|')){
					list($confval, $dot) = explode('|', $val);
				}else{
					$confval = $val;
					$dot = "==";
				}
				if($this->showDetail){
					echo $key," ",$dot," ",$confval,"  ? ";
				}
				if(strpos($key, 'ata.')>0 && 'json_decode' == $this->_config['common']['parsetype']){
					//找到类似酱紫的变量data.0.id,转换成什么呢？ $data[0]['id'];
					$ary = explode('.', $key);
					$tmp = $data;
					for($i=1; $i<count($ary); $i++){
						$tmp = $tmp[$ary[$i]];
					}
					$value = $tmp;
				}else{

					$value = $data[$key];
				}
				if($this->showDetail){
					echo "[", is_array($value) || is_object($value) ? json_encode($value) : $value,"]\n";
				}
				switch ($dot) {
					case '==':
						if($value != $confval){
							return false;
						}
						break;
					case '>':
						if($value <= $confval){
							
							return false;
						}
						break;
					case '<':
						if($value >= $confval){
							
							return false;
						}
						break;
					case '>=':
						if($value < $confval){
							
							return false;
						}
						break;
					case '<=':
						if($value > $confval){
							
							return false;
						}
						break;
					case 'con':
						if(!preg_match('/{$confval}/', $value)){
							
							return false;
						}
						break;
					default:
						return false;
						break;
				}
			} //End foreach
		}
		return true;
	}
}

$file = isset($argv[1]) ? $argv[1] : '';
$showDetail = isset($argv[2]) && $argv[2] == '-v' ? true : false;
$case = new punitFrame($file, $showDetail);
$case ->parse();
$result = $case ->getResult();
?>
