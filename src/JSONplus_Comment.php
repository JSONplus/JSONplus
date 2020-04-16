<?php
namespace JSONplus;
require_once('JSONplus.php');

class Comment extends \JSONplus {
  var $comment = NULL;
  var $position = 'prefix'; #inline|prefix|postfix
  var $type = 'encapsule'; # /* encapsule */    #additional    //slashed
  public function export($setting=array()){
		$current = $this->_get_current($setting);
    $com = $str = NULL;
    if(isset($setting['type'])){ $o->set_type($setting['type']); }
    if(isset($setting['position'])){ $o->set_position($setting['position']); }
    if(\JSONplus::is_JSONplus($current)){
      $str = $current->export($setting);
    }
    else {
      $str = (new \JSONplus())->export(array_merge($setting, array('current'=>$current)));
			//$str = \JSONplus::encode($current);
    }
    $line = NULL; $depth = NULL; $end = NULL;
    if(isset($setting['line'])){
      $line = $setting['line'];
      $depth = preg_replace('#^(\s*)(.*)$#', '\\1', $line);
      if(preg_match('#^(.*)([\,\]\}]\s*)$#', $line, $buffer)){ $line = $buffer[1]; $end = $buffer[2]; }
    }
    /*fix*/ $str = \JSONplus\Comment::prefix_depth_each_line($str, $depth, JSONplus_EOL); $str = preg_replace('#^'.$depth.'#', '', $str);
    /*fix*/ if($this->position == 'inline' && $this->type != 'encapsule'){ $this->set_position('after'); }

    $setting['line'] = $line; $setting['end'] = $end; //$setting['depth'] = $depth;
    switch(strtolower($this->type)){
      case 'encapsule': case '/**/': $com = '/* '.$this->comment.' */'; break;
      case 'additional': case '#': $com = '#'.$this->comment; break;
      case 'slashed': case '//': $com = '//'.$this->comment; break;
    }
    switch(strtolower($this->position)){
      case 'solo': $str = $depth.$com; break;
      case 'prefix': $str = $depth.$com.JSONplus_EOL.$this->_line($str, $setting); break;
      case 'after': $str = $this->_line($str, $setting).' '.$com; break;
      case 'inline': $str = $this->_line($com.' '.$str, $setting); break;
      case 'postfix': $str = $this->_line($str, $setting).JSONplus_EOL.$depth.$com; break;
    }
    return $str;
  }
  static function prefix_depth_each_line($str, $depth=NULL, $eol=JSONplus_EOL){
    if(is_int($depth)){ $depth = str_repeat("\t", $depth); }
    $set = explode($eol, $str);
    foreach($set as $i=>$s){
      $set[$i] = $depth.$s;
    }
    $str = implode($eol, $set);
    return $str;
  }
  public function set_comment($comment){
    $this->comment = $comment;
  }
  public function set_position($p=NULL){
    $pa = array('after','solo','inline','postfix','prefix');
    if(is_array($p)){ return $pa; }
    elseif(is_string($p) && strlen($p)>0){
      if(in_array(strtolower($p), $pa)){ $this->position = strtolower($p); }
      else{ $this->position = reset($pa); }
    }
    return $this->position;
  }
  public function set_type($t=NULL){
    $ta = array('encapsule','additional','slashed');
    if(is_array($t)){ return $ta; }
    elseif(is_string($t) && strlen($t)>0){
      if(in_array(strtolower($t), $ta)){ $this->type = strtolower($t); }
      else{ $this->type = reset($ta); }
    }
    return $this->type;
  }
  static function create($y=NULL, $x=\JSONplus::EMPTY, $setting=array()){
    $o = new \JSONplus\Comment($x);
    $o->set_comment($y);
    if(isset($setting['type'])){ $o->set_type($setting['type']); }
    if(isset($setting['position'])){ $o->set_position($setting['position']); }
    return $o;
  }
}
?>
