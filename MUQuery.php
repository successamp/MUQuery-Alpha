<?php
/**
 * MUQuery
 *
 * An PHP library to work with HTML as an extendable PHP object.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst.  It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * paul.dillinger@gmail.com so we can send you a copy immediately.
 *
 * @package		MUQuery
 * @author		Paul Dillinger
 * @copyright	Copyright (c) 2010 - 2012, Paul R. Dillinger. (http://prd.me/)
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://muquery.com
 * @since		Version 1.0
 * @filesource
 */
class MUQuery {

	private $output = array();
          // This is where we store the array of code for output
          private $buffer = '';
          // Once the code has been built using ->get() it lives here until ->get() is called again.
          private $lang = '';
          // Once the code has been built using ->get() it lives here until ->get() is called again.
          private $open = array();
          // Array of open tags.
          private $insert = array();
          // Insert a tag after this.
          private $selector_vars = array();

	public function __construct($lang=NULL) {
	          switch(strtoupper($lang)){
                              case 'X' :
                                        $this->lang = 'X'; // XHTML 1.1
                                        break;
                              case 'H' :
                                        $this->lang = 'H'; // HTML 4.01
                                        break;
                              default :
                                        $this->lang = '5'; // HTML 5
                                        break;
	          }
                    $this->selector_vars['type'] = '';
                    $this->selector_vars['value'] = '';
                    $this->selector_vars['keys'] = array();
	}

          public function selector($select){
                    // FUTURE -- Select by .class #id or get_tag
                    $this->selector_vars['type'] = '';
                    $this->selector_vars['value'] = '';
                    $this->selector_vars['keys'] = array();
	          switch(strtolower($select[0])){
                              case '#' : // ID
                                        $this->selector_vars['type'] = 'id';
                                        $this->selector_vars['value'] = substr($select, 1);
                                        foreach($this->output as $key => $item){
                                                  if(!empty($item['attributes']['id']) && $item['attributes']['id'] == $this->selector_vars['value']){
                                                            $this->selector_vars['keys'][] = $key;
                                                  }
                                        }
                                        break;
                              case '.' : // Class
                                        $this->selector_vars['type'] = 'class';
                                        $this->selector_vars['value'] = substr($select, 1);
                                        foreach($this->output as $key => $item){
                                                  if(!empty($item['attributes']['class']) && $item['attributes']['class'] == $this->selector_vars['value']){
                                                            $this->selector_vars['keys'][] = $key;
                                                  }
                                        }
                                        break;
                              case 'h' : // Heading
                                        if($select[1] > 0 && $select[1] < 7 ){
                                                  $this->selector_vars['type'] = 'tag';
                                                  $this->selector_vars['value'] = 'get_h';
                                                  foreach($this->output as $key => $item){
                                                            if(!empty($item['tag']) && $item['tag'] == $this->selector_vars['value'] && $item['attributes']['level'] == $select[1]){
                                                                      $this->selector_vars['keys'][] = $key;
                                                            }
                                                  }
                                        }else{
                                                  $this->selector_vars['type'] = 'tag';
                                                  $this->selector_vars['value'] = 'get_'.$select;
                                                  foreach($this->output as $key => $item){
                                                            if(!empty($item['tag']) && $item['tag'] == $this->selector_vars['value']){
                                                                      $this->selector_vars['keys'][] = $key;
                                                            }
                                                  }
                                        }
                                        break;
                              default : // Other Tag
                                        $this->selector_vars['type'] = 'tag';
                                        $this->selector_vars['value'] = 'get_'.$select;
                                        foreach($this->output as $key => $item){
                                                  if(!empty($item['tag']) && $item['tag'] == $this->selector_vars['value']){
                                                            $this->selector_vars['keys'][] = $key;
                                                  }
                                        }
                                        break;
	          }
                    return $this;
          }

          private function get_keys(){
                    return empty($this->selector_vars['keys']) ? array(count($this->output) -1) : $this->selector_vars['keys'];
          }

          private function update_keys($key){
                    if(!empty($this->selector_vars['keys'])){
                              unset($this->selector_vars['keys'][$key]);
                    }
          }

          private function set_output($val){
                    if(empty($this->insert)){
                              $this->output[] = $val;
                    }else{
                              for($x=count($this->insert);$x>0;$x--){
                                        $key = array_pop($this->open);
                                        $this->output = array_splice($this->output, $key, 0, $val);
//A reference is made to INSERT'ing into an array here with array_splice, however its not explained very well.  I hope this example will help others find what took me days to research.
//$original_array = array(1,2,3,4,5);
//$insert_into_key_position = 3;
//$item_to_insert = "blue";
//$returned = array_splice($original_array, $insert_into_key_position, 0, $item_to_insert);
// $original_array will now show:
// 1,2,3,blue,4,5
                              }

                    }
          }

          /*********************************************************************
          ** ATTRIBUTES
          *********************************************************************/

          public function attr($attr=NULL, $value=NULL){
                    foreach($this->get_keys() as $key => $x){
                              $this->output[$x]['attributes'][$attr] = $value;
                              $this->update_keys($key);
                    }
                    return $this;
          }

          public function id($value=NULL){
                    foreach($this->get_keys() as $key => $x){
                              $this->output[$x]['attributes']['id'] = $value;
                              $this->update_keys($key);
                    }
                    return $this;
          }

          public function addClass($value=NULL){
                    foreach($this->get_keys() as $key => $x){
                              $this->output[$x]['attributes']['class'] = empty($this->output[$x]['attributes']['class']) ? $value : $this->output[$x]['attributes']['class'] . $value;
                              $this->update_keys($key);
                    }
                    return $this;
          }

          public function removeClass($value=NULL){
                    foreach($this->get_keys() as $key => $x){
                              if(!empty($this->output[$x]['attributes']['class'])){
                                        $classes = explode(' ',$this->output[$x]['attributes']['class']);
                                        $this->output[$x]['attributes']['class'] = '';
                                        foreach($classes as $class){
                                                  if($class != $value){
                                                            $this->output[$x]['attributes']['class'] .= $class;
                                                  }
                                        }
                              }
                              $this->update_keys($key);
                    }
                    return $this;
          }

          public function open(){
                    $key = count($this->output) -1;
                    $this->output[$key]['attributes']['open'] = TRUE;
                    switch($this->output[$key]['tag']){
                              case 'get_a':
                                        $this->open[] = '</a>';
                                        break;
                              case 'get_p':
                                        $this->open[] = '</p>';
                                        break;
                              case 'get_h':
                                        $this->open[] = '</h'.$this->output[$key]['attributes']['level'].'>';
                                        break;
                              case 'get_form':
                                        $this->open[] = '</form>';
                                        break;
                    }
                    return $this;
          }

          public function close(){
                    $key = array_pop($this->open);
                    $val['tag'] = 'get_close';
                    $val['attributes']['text'] = $key;
                    $this->output[] = $val;
                    return $this;
          }

          /*********************************************************************
          ** TAGS
          *********************************************************************/

          public function a($text=NULL, $href=NULL){
                    $val = array();
                    $val['tag'] = 'get_a';
                    if(is_array($text)){
                              $val['attributes'] = $text;
                    }else{
                              $val['attributes']['text'] = $text;
                              $val['attributes']['href'] = $href;
                    }
                    $this->set_output($val);
                    return $this;
          }

          public function p($text=NULL){
                    $val = array();
                    $val['tag'] = 'get_p';
                    if(is_array($text)){
                              $val['attributes'] = $text;
                    }else{
                              $val['attributes']['text'] = $text;
                    }
                    $this->set_output($val);
                    return $this;
          }

          public function h($text=NULL, $level=1){
                    $val = array();
                    $val['tag'] = 'get_h';
                    if(is_array($text)){
                              $val['attributes'] = $text;
                    }else{
                              $val['attributes']['text'] = $text;
                              $val['attributes']['level'] = $level;
                    }
                    $this->set_output($val);
                    return $this;
          }

          public function hr($class=NULL){
                    $val = array();
                    $val['tag'] = 'get_hr';
                    if(is_array($class)){
                              $val['attributes'] = $text;
                    }else{
                              $val['attributes']['class'] = $class;
                    }
                    $this->set_output($val);
                    return $this;
          }

          // Form item defualts to open
          public function form($action=NULL, $method='post', $open=TRUE){
                    $val = array();
                    $val['tag'] = 'get_form';
                    if(is_array($action)){
                              $val['attributes'] = $action;
                    }else{
                              $val['attributes']['action'] = $action;
                              $val['attributes']['method'] = $method;
                              $val['attributes']['open'] = $open;
                              if($open === TRUE){
                                        $this->open[] = '</form>';
                              }
                    }
                    $this->set_output($val);
                    return $this;
          }

          public function input($name='', $type='text', $value='', $short=TRUE){
                    $val = array();
                    $val['tag'] = 'get_input';
                    if(is_array($name)){
                              $val['attributes'] = $name;
                    }else{
                              $val['attributes']['name'] = $name;
                              $val['attributes']['value'] = $value;
                              $val['attributes']['type'] = $type;
                              $val['attributes']['short'] = $short;
                    }
                    $this->set_output($val);
                    return $this;
          }

          public function textarea($name='', $value=''){
                    $val = array();
                    $val['tag'] = 'get_textarea';
                    if(is_array($name)){
                              $val['attributes'] = $name;
                    }else{
                              $val['attributes']['name'] = $name;
                              $val['attributes']['value'] = $value;
                    }
                    $this->set_output($val);
                    return $this;
          }

          public function select($name='', $class=NULL, $open=TRUE){
                    $val = array();
                    $val['tag'] = 'get_select';
                    if(is_array($name)){
                              $val['attributes'] = $name;
                    }else{
                              $val['attributes']['name'] = $name;
                              $val['attributes']['class'] = $class;
                              $val['attributes']['open'] = $open;
                              if($open === TRUE){
                                        $this->open[] = '</select>';
                              }
                    }
                    $this->set_output($val);
                    return $this;
          }

          public function option($text='', $value=NULL, $force_value=FALSE){
                    $val = array();
                    $val['tag'] = 'get_option';
                    if(is_array($text)){
                              $val['attributes'] = $text;
                    }else{
                              $val['attributes']['text'] = $text;
                              $val['attributes']['value'] = $value;
                              $val['attributes']['force_value_on_empty'] = $force_value;
                    }
                    $this->set_output($val);
                    return $this;
          }

          public function label($name='', $text='', $class=''){
                    $val = array();
                    $text = empty($text) ? $name : $text;
                    $val['tag'] = 'get_label';
                    if(is_array($name)){
                              $val['attributes'] = $name;
                    }else{
                              $val['attributes']['name'] = $name;
                              $val['attributes']['text'] = $text;
                              $val['attributes']['class'] = $class;
                    }
                    $this->set_output($val);
                    return $this;
          }

          /*********************************************************************
          ** OUTPUT
          *********************************************************************/

          public function get(){
                    if(count($this->open) != 0){
                              print_r($this->open);
                              throw new Exception('The content can not be returned with unclosed tags');
                    }
                    $returnVal = '';
                    if(!empty($this->output)){
                              foreach($this->output as $item){
                                        if(!empty($item)){
                                                  $returnVal .= $this->{$item['tag']}($item['attributes']);
                                        }
                              }
                              $this->buffer = $returnVal;
                    }else{
                              $returnVal = $this->buffer;
                    }
                    $this->output = array();
                    return $returnVal;
          }

          public function get_close($attr){
                    $val = empty($attr['text']) ? '' : $attr['text']; // Close a previously opened tag
                    return $val;
          }

          public function get_a($attr){
                    $val = '<a';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['href']) ? '' : ' href="'.$attr['href'].'"';
                    $val .= empty($attr['rel']) ? '' : ' rel="'.$attr['rel'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    $val .= empty($attr['tabindex']) ? '' : ' tabindex="'.$attr['tabindex'].'"';
                    $val .= '>';
                    $val .= empty($attr['text']) ? '' : $attr['text'];
                    $val .= empty($attr['open']) ? '</a>' : '';
                    return $val;
          }

          public function get_p($attr){
                    $val = '<p';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    $val .= '>';
                    $val .= empty($attr['text']) ? '' : $attr['text'];
                    $val .= empty($attr['open']) ? '</p>' : '';
                    return $val;
          }

          public function get_h($attr){
                    $val = '<h'.$attr['level'];
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    $val .= '>';
                    $val .= empty($attr['text']) ? '' : $attr['text'];
                    $val .= empty($attr['open']) ? '</h'.$attr['level'].'>' : '';
                    return $val;
          }

          public function get_hr($attr){
                    $val = '<hr';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= $this->lang == 'X' ? '/>' : '>';
                    return $val;
          }

          public function get_form($attr){
                    $val = '<form';
                    $val .= empty($attr['action']) ? '' : ' action="'.$attr['action'].'"';
                    $val .= empty($attr['method']) ? '' : ' method="'.$attr['method'].'"';
                    $val .= empty($attr['accept-charset']) ? '' : ' accept-charset="'.$attr['accept-charset'].'"';
                    $val .= empty($attr['enctype']) ? '' : ' enctype="'.$attr['enctype'].'"';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    $val .= '>';
                    $val .= empty($attr['text']) ? '' : $attr['text'];
                    $val .= empty($attr['open']) ? '</form>' : '';
                    return $val;
          }

          public function get_input($attr){
                    $val = '<input';
                    $val .= empty($attr['accept']) ? '' : ' accept="'.$attr['accept'].'"';
                    $val .= empty($attr['alt']) ? '' : ' alt="'.$attr['alt'].'"';
                    $val .= empty($attr['checked']) ? '' : ' checked="'.$attr['checked'].'"';
                    $val .= empty($attr['disabled']) ? '' : ' disabled="'.$attr['disabled'].'"';
                    $val .= empty($attr['maxlength']) ? '' : ' maxlength="'.$attr['maxlength'].'"';
                    $val .= empty($attr['name']) ? '' : ' name="'.$attr['name'].'"';
                    $val .= empty($attr['readonly']) ? '' : ' readonly="'.$attr['readonly'].'"';
                    $val .= empty($attr['size']) ? '' : ' size="'.$attr['size'].'"';
                    $val .= empty($attr['src']) ? '' : ' src="'.$attr['src'].'"';
                    $val .= empty($attr['type']) ? '' : ' type="'.$attr['type'].'"';
                    $val .= empty($attr['value']) ? '' : ' value="'.$attr['value'].'"';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    if(empty($attr['short'])){
                              $val .= '>';
                              $val .= empty($attr['text']) ? '' : $attr['text'];
                              $val .= empty($attr['open']) ? '</input>' : '';
                    }else{
                              $val .= '/>';
                    }
                    return $val;
          }

          public function get_textarea($attr){
                    $val = '<textarea';
                    $val .= empty($attr['cols']) ? '' : ' cols="'.$attr['cols'].'"';
                    $val .= empty($attr['rows']) ? '' : ' rows="'.$attr['rows'].'"';
                    $val .= empty($attr['disabled']) ? '' : ' disabled="'.$attr['disabled'].'"';
                    $val .= empty($attr['name']) ? '' : ' name="'.$attr['name'].'"';
                    $val .= empty($attr['readonly']) ? '' : ' readonly="'.$attr['readonly'].'"';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    $val .= '>';
                    $val .= empty($attr['value']) ? '' : $attr['value'];
                    $val .= empty($attr['open']) ? '</textarea>' : '';
                    return $val;
          }

          public function get_select($attr){
                    $val = '<select';
                    $val .= empty($attr['name']) ? '' : ' name="'.$attr['name'].'"';
                    $val .= empty($attr['disabled']) ? '' : ' disabled="'.$attr['disabled'].'"';
                    $val .= empty($attr['multiple']) ? '' : ' multiple="multiple"';
                    $val .= empty($attr['size']) ? '' : ' size="'.$attr['size'].'"';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    $val .= '>';
                    return $val;
          }

          public function get_option($attr){
                    $val = '<option';
                    if(empty($attr['force_value_on_empty'])){
                        $val .= empty($attr['value']) ? '' : ' value="'.$attr['value'].'"';
                    }else{
                        $val .= ' value="'.$attr['value'].'"';
                    }
                    $val .= empty($attr['disabled']) ? '' : ' disabled="'.$attr['disabled'].'"';
                    $val .= empty($attr['selected']) ? '' : ' selected="selected"';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    $val .= '>';
                    $val .= empty($attr['text']) ? '' : $attr['text'];
                    $val .= '</option>';
                    return $val;
          }

          public function get_label($attr){
                    $val = '<label';
                    $val .= empty($attr['for']) ? '' : ' for="'.$attr['for'].'"';
                    $val .= empty($attr['id']) ? '' : ' id="'.$attr['id'].'"';
                    $val .= empty($attr['class']) ? '' : ' class="'.$attr['class'].'"';
                    $val .= empty($attr['style']) ? '' : ' style="'.$attr['style'].'"';
                    $val .= empty($attr['title']) ? '' : ' title="'.$attr['title'].'"';
                    $val .= '>';
                    $val .= empty($attr['text']) ? '' : $attr['text'];
                    $val .= empty($attr['open']) ? '</label>' : '';
                    return $val;
          }
}
