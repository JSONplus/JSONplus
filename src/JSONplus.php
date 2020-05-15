<?php
if(!defined("JSONplus_DATALIST_ROOT")){define("JSONplus_DATALIST_ROOT", dirname(__FILE__).'/');}
if(!defined("JSONplus_FILE_ARGUMENT")){define("JSONplus_FILE_ARGUMENT", 'file');}
if(!defined("JSONplus_POST_ARGUMENT")){define("JSONplus_POST_ARGUMENT", 'json');}
if(!defined("JSONplus_EOL")){define("JSONplus_EOL", PHP_EOL);}
if(!defined("JSONplus_TAB")){define("JSONplus_TAB", "\t");}
if(!defined("JSONplus_SELECTIVE")){define("JSONplus_SELECTIVE", FALSE);}

if(!class_exists('JSONplus\JSON')){ require_once('JSONplus_JSON.php'); }
if(!class_exists('JSONplus\Comment')){ require_once('JSONplus_Comment.php'); }

class JSONplus {
	const NOT_FOUND_ERROR = NULL;
	const MALFORMED_ERROR = -4;
	const INCORRECT = -3;
	const UNSUPPORTED = FALSE;
	const EMPTY = array();

  var $uri = \JSONplus::NOT_FOUND_ERROR;
	var $name = \JSONplus::NOT_FOUND_ERROR;
  var $_ = \JSONplus::EMPTY;
  var $schema = \JSONplus::UNSUPPORTED;

  function __construct($x=\JSONplus::EMPTY, $setting=array()){
		/*fix*/ if(is_string($setting)){ $setting = array('mode'=>$setting); }
		switch(gettype($x)){
			case 'string':
				if(preg_match('#^\s*[\[\{]#', $x)){
					/*todo: rewrite*/ $this->_ = \JSONplus::decode($x, TRUE);
				}
				elseif(file_exists($x)){
					$this->import_file($x);
				}
				else{
					//$this->__e('MALFORMED_ERROR');
					$this->_ = $x;
				}
				break;
			case 'object': $this->_ = $x; break;
			case 'array':
			case 'NULL': case 'boolean': case 'integer': case 'double':
				$this->load($x); break;
			default: //do nothing
		}
		if(isset($this->_['$schema'])){ $this->schema &= $this->_['$schema']; }
		if(isset($setting['name'])){ $this->set_name($setting['name']); }
		if(isset($setting['comment']) && class_exists('\JSONplus\Comment')){
			$cset = $setting; unset($cset['comment']);
			$this->_ = \JSONplus\Comment::create($setting['comment'], $this->_, $cset);
		}
  }
	private function __e($code, $method=NULL, $params=array()){
    /*todo*/
		if(is_string($code)){ $code = strtoupper($code); }
		switch($code){
			case 'NOT_FOUND_ERROR': case \JSONplus::NOT_FOUND_ERROR: $code = \JSONplus::NOT_FOUND_ERROR; break;
			case 'MALFORMED_ERROR': case \JSONplus::MALFORMED_ERROR: $code = \JSONplus::MALFORMED_ERROR; break;
			case 'INCORRECT': case \JSONplus::INCORRECT: $code = \JSONplus::INCORRECT; break;
			case 'EMPTY': case \JSONplus::EMPTY: $code = \JSONplus::EMPTY; break;
			case 'UNSUPPORTED': case \JSONplus::UNSUPPORTED: default: $code = \JSONplus::UNSUPPORTED;
		}
		//implement some error logging
		return $code;
	}
  public function __toString(){
    if(\JSONplus::is_JSONplus($this->_)){
      return $this->_->__toString();
    }
    else {
      if(!defined('JSONplus_SELECTIVE') || constant('JSONplus_SELECTIVE') == FALSE ){
  			return $this->export();
      }
      else {
        return $this->process();
      }
    }
  }
  public function __toArray(){
    if(\JSONplus::is_JSONplus($this->_)){
      return $this->_->__toArray();
    }
    else{ return $this->_; }
  }
  public function get(){
    return $this->__toArray();
  }
  public function get_uri(){
    return $this->uri;
  }
  public function set_uri($file){
    $this->uri = $file;
  }
  public function get_name(){
    return $this->name;
  }
  public function set_name($n){
    $this->name = $n;
  }
  public function load($json=array()){
    $this->_ = $json;
  }
  public function merge($json=array()){
    /*todo*/
  }
  static function open($file=FALSE){
    $j = new self();
    return $j->import_file($file);
  }
  static function save($file=FALSE, $json=array()){
    $j = new self($json);
    return $j->export_file($file);
  }
	static function file($file){
		$j = new self($file);
		return $j;
	}
	static function str($str=NULL){
		$j = new self($str);
		return $j;
	}
  public function analyse($str=NULL, $setting=array()){
    /*todo*/
    return $this->import($str, $setting);
  }
  static function uncode($str, $setting=array()){
    $j = new self();
    $j->analyse($str, $setting);
    return $j->get();
  }
  public function match($str=NULL, $setting=array()){
    /*todo*/
    return FALSE;
  }
  public function import($str=NULL, $setting=array()){
    if(\JSONplus::is_JSONplus($this->_)){
      return $this->_->import($str, $setting);
    }
    else {
			if(preg_match('#^\s*[\[\{]#', $str)){
				/*todo: rewrite*/ $json = \JSONplus::decode($str, TRUE);
				$this->load($json);
				return $json;
			}
			else{
				return $this->__e('MALFORMED_ERROR', __METHOD__, array('file'=>$file));
			}
    }
  }
  public function import_file($file=FALSE, $setting=array()){
    if($file !== FALSE){ $this->uri = $file; }
    if(\JSONplus::is_JSONplus($this->_)){
      return $this->_->import_file($file, $setting);
    }
    else {
			if(!file_exists($file)){
				return $this->__e('NOT_FOUND_ERROR', __METHOD__, array('file'=>$file));
			}
			$raw = file_get_contents($file);
			return $this->import($raw, $setting);
      //return \JSONplus::UNSUPPORTED;
    }
  }
  public function process($setting=array()){
    /*todo*/
    return $this->export($setting);
  }
  static function recode($json=array(), $setting=array()){
    $j = self($json);
    return $j->process($setting);
  }
  public function export($setting=array()){
		//todo: adding $this variables to $setting
		$current = $this->_get_current($setting);
		$setting = $this->_get_setting($setting);
		switch(gettype($current)){
			case 'object':
				if(\JSONplus::is_JSONplus($current)){
					return $current->export($setting);
				}
				else{
					return (string) $current;
				}
				break; //*
			case 'array':
				if(count($current) == 0){ $str = '[]'; }
				else{
					if(array_key_first($current) === 0){
						$str = '[';
						$i = 1;
						foreach($current as $key=>$val){
							$str .= JSONplus_EOL;
							$line = $setting['depth'].JSONplus_TAB;
							$str .= $this->export(array_merge($setting, array('current'=>$val,'line'=>$line)));
							$str .= ($i == count($current) ? NULL : ',');
							$i++;
						}
						$str .= JSONplus_EOL.$setting['depth'].']';
						$str = $this->_line($str, $setting);
					}
					else{
						$str = '{';
						$i = 1;
						foreach($current as $key=>$val){
							$str .= JSONplus_EOL;
							$line = $setting['depth'].JSONplus_TAB.'"'.$key.'": ';
							$str .= $this->export(array_merge($setting, array('current'=>$val,'line'=>$line)));
							$str .= ($i == count($current) ? NULL : ',');
							$i++;
						}
						$str .= JSONplus_EOL.$setting['depth'].'}';
						$str = $this->_line($str, $setting);
					}
				}
				return $str;
				break; //*/
			case 'string': return $this->_line('"'.$current.'"', $setting); break;
			case 'integer': case 'double': return $this->_line(($current == 0 ? '0' : (string) $current), $setting); break;
			case 'boolean': return $this->_line(($current == TRUE ? 'true' : 'false'), $setting); break;
			case 'NULL': return $this->_line('null', $setting); break;
			default:
				/*todo: rewrite, might not be needed at all!*/ return \JSONplus::encode($current);
		}
    return \JSONplus::UNSUPPORTED;
  }
	function _get_current($setting=array()){
		if(isset($setting['current'])){
			$current = $setting['current']; unset($setting['current']);
		}
		else {
			$current = $this->_;
		}
		return $current;
	}
	function _get_setting($setting=array()){
		$setting['depth'] = (isset($setting['depth']) ? $setting['depth'] : NULL);
		$setting['line'] = (isset($setting['line']) ? $setting['line'] : NULL);
		$setting['end'] = (isset($setting['end']) ? $setting['end'] : NULL);
		if(isset($setting['line'])){
      $setting['depth'] = preg_replace('#^(\s*)(.*)$#', '\\1', $setting['line']);
      if(preg_match('#^(.*)([\,\]\}]\s*)$#', $setting['line'], $buffer)){ $setting['line'] = $buffer[1]; $setting['end'] = $buffer[2]; }
    }
		return $setting;
	}
	function _line($str, $setting=array()){
		if(preg_match('#(^|'.JSONplus_EOL.')('.$setting['line'].')#', $str)){
			return $str;
		}
		else {
			return $setting['line'].$str.$setting['end'];
		}
	}
  public function export_file($file=FALSE, $setting=array()){
    if($file === FALSE){ $file = $this->uri; }
    if(\JSONplus::is_JSONplus($this->_)){
      return $this->_->export_file($file, $setting);
    }
    else {
      //return $this->__e('UNSUPPORTED');
      $raw = $this->export($setting);
      return file_put_contents($file, $raw);
    }
  }
  /***********************************************************
   * TYPE VALIDATION AND SCHEMA *
   ***********************************************************/
  static function is($o=FALSE){
    /*todo*/
    $j = new self();
    return $j->match($o);
    //return $this->__e('MALFORMED_ERROR');
  }
  static function get_extension($multiple=FALSE){
    $ext = array('json');
    if($multiple === FALSE){ return reset($ext); }
    else{ return $ext; }
  }
  public function get_schema(){
    return $this->schema;
  }
  public function set_schema($schema=FALSE){
    if(\JSONplus::is_JSONplus($schema, 'JSONplus\Schema')){
      $this->schema = $schema;
    }
    elseif(class_exists('\JSONplus\Schema') && is_string($schema) && file_exists($schema)){
      $this->schema = new \JSONplus\Schema($schema);
    }
    else{
      return $this->__e('MALFORMED_ERROR');
    }
  }
  public function validate(){
    /*todo*/
    return $this->__e('UNSUPPORTED');
  }

  /***********************************************************
   * ID *
   ***********************************************************/
	static function ID_crawl($json=array(), $prefix=NULL, $pattern=FALSE, $schema=FALSE, $allow_multiple=FALSE){
		$set = array();
		/*fix*/ if(strlen($prefix) < 1){ $prefix = '/'; }
		/*fix*/ if(is_bool($pattern)){ $pattern = array('#^[\$]?id$#i'); }
		if(FALSE){
			// $schema tells $prefix should be considered to be an ID by #{basename($prefix)}
		}
		foreach($json as $key=>$child){
			if(is_string($child)){
				foreach($pattern as $q=>$p){
					if(/*considered to be an ID*/ preg_match($p, $key)){
						//if(/*valid ID name*/ preg_match('#^[a-z0-9]$#i', $child)){
							if($allow_multiple === TRUE){ $set[] = array('source'=>$child, 'target'=>$prefix); }
							else { $set[$child] = $prefix; }
						//} //else {}
					}
				}
			}
			elseif(is_array($child)){
				$set = array_merge($set, \JSONplus::ID_crawl($child, (substr($prefix, -1) != '/' ? $prefix.'/' : $prefix).$key, $pattern, $schema, $allow_multiple));
			}
		}
		return $set;
	}
	public function ID_table(){
		return \JSONplus::ID_crawl($this->_, NULL, FALSE, $this->schema);
	}

  /***********************************************************
   * PATH AND POINTERS *
   ***********************************************************/
	public function getByPath($path){
    /*todo*/
		return $this->__e('EMPTY');
	}
	public function getByID($id){
    /*todo*/
		return $this->__e('EMPTY');
	}
	static function pointer($path, $json=array()){
    /*todo*/
		return \JSONplus::EMPTY;
	}

  /***********************************************************
   * STATIC *
   ***********************************************************/

  static function create($y=NULL, $x=\JSONplus::EMPTY, $setting=array()){
		$o = new self($x, (isset($setting['mode']) ? $setting['mode'] : 'JSON'));
		if($y!=NULL){ $o->set_name($y); }
		return $o;
	}
 	static function is_JSONplus($o, $c='JSONplus'){
		if(!preg_match('#JSONplus#', $c)){ return \JSONplus::UNSUPPORTED; }
		if(is_array($c)){
			$bool = FALSE;
			foreach($c as $d){
				$w = \JSONplus::is_JSONplus($o, $d);
				if($w === \JSONplus::UNSUPPORTED){ return \JSONplus::UNSUPPORTED; }
				$bool = ($bool || $w);
			}
			return $bool;
		}
		else{
			return ((is_object($o) && (get_class($o) == $c || is_subclass_of($o, $c) ) ) ? TRUE : FALSE);
		}
 	}
  static function encode($value=\JSONplus::NOT_FOUND_ERROR, $options=0, $depth=512){
    /*todo*/
		//return (new \JSONplus($value))->export();
		if($value === \JSONplus::NOT_FOUND_ERROR && isset($this)){ $value = $this->_; }
		$str = json_encode($value, $options, $depth);
		//pretty print (human readable and support for GiT-version management)
		$str = \JSONplus::prettyPrint($str);
		/*fix*/ $str = \JSONplus::printfixes($str);
		$str = \JSONplus::unhide_negative_keys($str);
		return $str;
	}
	static function /*json*/ decode($str, $assoc=FALSE, $depth=512, $options=0){
    /*todo*/
		//proces <datalist:*> before return
		$str = \JSONplus::include_all_datalist($str);
		$str = \JSONplus::hide_negative_keys($str);
		if(isset($options) && !($options===0) ){ $json = json_decode($str, $assoc, $depth, $options); }
		elseif(isset($depth) && !($depth===512) ){ $json = json_decode($str, $assoc, $depth); }
		elseif(isset($assoc) && !($assoc===FALSE) ){ $json = json_decode($str, $assoc); }
		else{ $json = json_decode($str); }
		$json = \JSONplus::fix_negative_keys($json);
		return $json;
	}
	static function hide_negative_keys($str){
		if(preg_match_all('#([\{\,]\s*)([\-]?[0-9]+)(\s*[\:])#', $str, $buffer)){
			foreach($buffer[2] as $i=>$negative){
				$str = str_replace($buffer[0][$i], $buffer[1][$i].'"'.$negative.'"'.$buffer[3][$i], $str);
			}
		}
		return $str;
	}
	static function unhide_negative_keys($str){
		if(preg_match_all('#([\{\,]\s*)[\"]([\-]?[0-9]+)[\"](\s*[\:])#', $str, $buffer)){
			foreach($buffer[2] as $i=>$negative){
				$str = str_replace($buffer[0][$i], $buffer[1][$i].$negative.$buffer[3][$i], $str);
			}
		}
		return $str;
	}
	static function fix_negative_keys($json=array()){
		/*fix*/ if(!is_array($json)){ return $json; }
		foreach($json as $key=>$val){
			if(is_string($key) && preg_match('#^[\-]?[0-9]+$#', $key)){ unset($json[$key]); $json[(int) $key] = $json; }
			if(is_array($val)){ $json[$key] = \JSONplus::fix_negative_keys($val); }
		}
		return $json;
	}
	static function printfixes($str){
		$str = str_replace('\/', '/', $str);
		return $str;
	}
	static function prettyPrint( $json ){
		$result = '';
		$level = 0;
		$in_quotes = false;
		$in_escape = false;
		$ends_line_level = NULL;
		$json_length = strlen( $json );

		for( $i = 0; $i < $json_length; $i++ ) {
			$char = $json[$i];
			$new_line_level = NULL;
			$post = "";
			if( $ends_line_level !== NULL ) {
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if ( $in_escape ) {
				$in_escape = false;
			} else if( $char === '"' ) {
				$in_quotes = !$in_quotes;
			} else if( ! $in_quotes ) {
				switch( $char ) {
					case '}': case ']':
						$level--;
						$ends_line_level = NULL;
						$new_line_level = $level;
						break;

					case '{': case '[':
						$level++;
					case ',':
						$ends_line_level = $level;
						break;

					case ':':
						$post = " ";
						break;

					case " ": case "\t": case "\n": case "\r":
						$char = "";
						$ends_line_level = $new_line_level;
						$new_line_level = NULL;
						break;
				}
			} else if ( $char === '\\' ) {
				$in_escape = true;
			}
			if( $new_line_level !== NULL ) {
				$result .= "\n".str_repeat( "\t", $new_line_level );
			}
			$result .= $char.$post;
		}

		return $result;
	}
  static function string_to_type_fix($v){
    if(is_string($v)){
      /* pattern according to https://www.php.net/manual/en/language.types.integer.php extended with negative and decimals */
      if(preg_match('#^[\-]?([1-9][0-9]*(_[0-9]+)*([\.][0-9]+)?|0|0[xX][0-9a-fA-F]+(_[0-9a-fA-F]+)*|0[0-7]+(_[0-7]+)*|0[bB][01]+(_[01]+)*)$#', $v)){ $v = (int) $v; }
      elseif(preg_match('#^(false|no)$#i', $v)){ $v = FALSE; }
      elseif(preg_match('#^(true|yes)$#i', $v)){ $v = TRUE; }
      elseif(preg_match('#^(NULL|)$#i', $v)){ $v = NULL; }
    }
    elseif(is_array($v)){
      foreach($v as $i=>$q){
        $v[$i] = self::string_to_type_fix($q);
      }
    }
    return $v;
  }

  /***********************************************************
   * DATALIST *
   ***********************************************************/
 	static function get_datalist($datalist){
 		return \JSONplus::decode(\JSONplus::open_datalist($datalist, '[]'), TRUE);
 	}
 	static function open_datalist($datalist, $errortype=FALSE){
 		if(!file_exists(JSONplus_DATALIST_ROOT.(substr(JSONplus_DATALIST_ROOT, -1) == '/' ? NULL : '/').$datalist.'.json')){ return $errortype; }
 		$str = file_get_contents(JSONplus_DATALIST_ROOT.(substr(JSONplus_DATALIST_ROOT, -1) == '/' ? NULL : '/').$datalist.'.json');
 		return $str;
 	}
 	static function include_all_datalist($json){
 		preg_match_all("#(\"[^\"]+\"\s*:\s*)?<datalist:([^>]+)>#i", $json, $buffer);
 		foreach($buffer[0] as $i=>$match){
 			if(strlen($buffer[1][$i]) >= 1){
 				$json = str_replace($buffer[0][$i], $buffer[1][$i].\JSONplus::open_datalist($buffer[2][$i], '[]'), $json);
 			} else {
 				$json = str_replace($buffer[0][$i], preg_replace("#^\s*[\{\[](.*)[\}\]]\s*$#i", "\\1", \JSONplus::open_datalist($buffer[2][$i], NULL)), $json);
 			}
 		}
 		return $json;
 	}

  /***********************************************************
  * WORKER *
  ***********************************************************/
	static function worker($mode='json'){
    $argv_list = array();
    if($_SERVER['argc'] > 0 && is_array($_SERVER['argv'])){ //case: php -f worker.php a=1 >> $_GET['a'] = 1
      foreach($_SERVER['argv'] as $i=>$par){
        if(preg_match('#^([^=]+)[=](.*)$#', $par, $buffer)){ $_GET[$buffer[1]] = $buffer[2]; }
        else{ $argv_list[$i] = $par; }
      }
    }

    if(isset($_GET[JSONplus_FILE_ARGUMENT]) && file_exists($_GET[JSONplus_FILE_ARGUMENT])){ //case: http://../worker.php?file=set.json
			$raw = file_get_contents($_GET[JSONplus_FILE_ARGUMENT]);
    }
    elseif(isset($argv_list[1]) && file_exists($argv_list[1])){ //case: php -f worker.php set.json
			$raw = file_get_contents($argv_list[1]);
    }

    if(!isset($raw)){ //case: cat set.json | php -f worker.php
      if(defined('STDIN') && php_sapi_name()==="cli"){
        $input = NULL;
        $fh = fopen('php://stdin', 'r');
        $read  = array($fh);
        $write = NULL;
        $except = NULL;
        if ( stream_select( $read, $write, $except, 0 ) === 1 ) {
            while ($line = fgets( $fh )) {
                    $input .= $line;
            }
        }
        fclose($fh);
				$raw = $input;
      }
      elseif(isset($_POST) && is_array($_POST) && isset($_POST[JSONplus_POST_ARGUMENT]) && is_string($_POST[JSONplus_POST_ARGUMENT])){ //case: http://../worker.php < $_POST['json']
				$raw = $_POST[JSONplus_POST_ARGUMENT];
      }
      else{
        //ERROR Message for worker
        //exit;
        return FALSE;
      }
    }
		switch(strtolower($mode)){
			case 'raw': return $raw; break;
			case 'json': default: return \JSONplus::decode($raw, TRUE);
		}
  }
}

/* array_key_first (php < 7.3.0) : https://www.php.net/manual/en/function.array-key-first.php */
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}
?>
