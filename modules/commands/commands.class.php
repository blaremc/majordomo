<?php
/**
* Commands 
*
* Commands
*
* @package MajorDoMo
* @author Serge Dzheigalo <jey@tut.by> http://smartliving.ru/
* @version 0.5 (wizard, 17:04:46 [Apr 09, 2009])
*/
//
//
class commands extends module {
/**
* commands
*
* Module class constructor
*
* @access private
*/
function commands() {
  $this->name="commands";
  $this->title="<#LANG_MODULE_CONTROL_MENU#>";
  $this->module_category="<#LANG_SECTION_OBJECTS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $data=array();
 if (IsSet($this->id)) {
  $data["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $data["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $data["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $data["tab"]=$this->tab;
 }
 if (IsSet($this->parent_item)) {
  $data["parent_item"]=$this->parent_item;
 }

 return parent::saveParams($data);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['TAB']=$this->tab;
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {

 global $ajax;
 if ($ajax) {

  global $op;
  global $item_id;


  if ($op=='get_label') {
   startMeasure('getLabel');
   $item=SQLSelectOne("SELECT * FROM commands WHERE ID='".(int)$item_id."'");
   startMeasure('getLabel '.$item['TITLE'], 1);
   if ($item['ID']) {
    $res=array();
    if ($item['TYPE']=='custom') {
     $res['DATA']=processTitle($item['DATA'], $this);
    } else {
     $res['DATA']=processTitle($item['TITLE'], $this);
    }
    echo json_encode($res);
   }
   endMeasure('getLabel '.$item['TITLE'], 1);
   endMeasure('getLabel',1);
   exit;   
  }

  if ($op=='get_value') {
   startMeasure('getValue');
   $item=SQLSelectOne("SELECT * FROM commands WHERE ID='".(int)$item_id."'");
   if ($item['ID']) {
    $res=array();
    $res['DATA']=$item['CUR_VALUE'];
    echo json_encode($res);
   }
   endMeasure('getValue',1);
   exit;   
  }


  if ($op=='value_changed') {
   global $new_value;
   $item=SQLSelectOne("SELECT * FROM commands WHERE ID='".(int)$item_id."'");
   if ($item['ID']) {
    $item['CUR_VALUE']=$new_value;
    SQLUpdate('commands', $item);
    if ($item['LINKED_PROPERTY']!='') {
     $old_value=gg($item['LINKED_OBJECT'].'.'.$item['LINKED_PROPERTY']);
     sg($item['LINKED_OBJECT'].'.'.$item['LINKED_PROPERTY'], $item['CUR_VALUE'], array('commands'=>'ID!='.$item['ID']));
     //DebMes("setting property ".$item['LINKED_OBJECT'].".".$item['LINKED_PROPERTY']." to ".$item['CUR_VALUE']);
    }

    $params=array('VALUE'=>$item['CUR_VALUE']);
    if (isSet($old_value)) {
     $params['OLD_VALUE']=$old_value;
    }

    if ($item['ONCHANGE_METHOD']!='') {
     getObject($item['ONCHANGE_OBJECT'])->callMethod($item['ONCHANGE_METHOD'], $params);
     //DebMes("calling method ".$item['ONCHANGE_OBJECT'].".".$item['ONCHANGE_METHOD']." with ".$item['CUR_VALUE']);
    }

    if ($item['SCRIPT_ID']) {
     //DebMes('Running on_change script #'.$item['SCRIPT_ID']);
     runScript($item['SCRIPT_ID'], $params);
    }
    if ($item['CODE']) {
     //DebMes("Running on_change code");
     
                  try {
                   $code=$item['CODE'];
                   $success=eval($code);
                   if ($success===false) {
                    DebMes("Error menu item code: ".$code);
                   }
                  } catch(Exception $e){
                   DebMes('Error: exception '.get_class($e).', '.$e->getMessage().'.');
                  }

    }

   }
   echo "OK";
  }
  exit;

 }



 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='commands' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_commands') {
   startMeasure('searchCommands');
   $this->search_commands($out);
   endMeasure('searchCommands', 1);
  }
  if ($this->view_mode=='edit_commands') {
   $this->edit_commands($out, $this->id);
  }

  if ($this->view_mode=='clone_commands') {
   $rec=SQLSelectOne("SELECT * FROM commands WHERE ID='".$this->id."'");
   unset($rec['ID']);
   $rec['TITLE']=$rec['TITLE'].' (copy)';
   $rec['ID']=SQLInsert('commands', $rec);
   $this->redirect("?id=".$rec['ID']."&view_mode=edit_commands");
  }

  if ($this->view_mode=='delete_commands') {
   $this->delete_commands($this->id);
   $this->redirect("?");
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* commands search
*
* @access public
*/
 function search_commands(&$out) {
  require(DIR_MODULES.$this->name.'/commands_search.inc.php');
 }
/**
* commands edit/add
*
* @access public
*/
 function edit_commands(&$out, $id) {
  require(DIR_MODULES.$this->name.'/commands_edit.inc.php');
 }


/**
* commands delete record
*
* @access public
*/
 function delete_commands($id) {
  $rec=SQLSelectOne("SELECT * FROM commands WHERE ID='$id'");
  // some action for related tables
   $tmp=SQLSelectOne("SELECT ID FROM commands WHERE PARENT_ID=".$rec['ID']);
   if ($tmp['ID']) {
    return;
   }

  SQLExec("DELETE FROM commands WHERE ID='".$rec['ID']."'");
 }
/**
* commands build tree
*
* @access private
*/
 function buildTree_commands($res, $parent_id=0, $level=0) {
  $total=count($res);
  $res2=array();
  for($i=0;$i<$total;$i++) {
   if ($res[$i]['PARENT_ID']==$parent_id) {
    $res[$i]['LEVEL']=$level;
    $res[$i]['RESULT']=$this->buildTree_commands($res, $res[$i]['ID'], ($level+1));
    $res2[]=$res[$i];
   }
  }
  $total2=count($res2);
  if ($total2) {
   return $res2;
  }
 }
/**
* commands update tree
*
* @access private
*/
 function updateTree_commands($parent_id=0, $parent_list='') {
  $table='commands';
  if (!is_array($parent_list)) {
   $parent_list=array();
  }
  $sub_list=array();
  $res=SQLSelect("SELECT * FROM $table WHERE PARENT_ID='$parent_id'");
  $total=count($res);
  for($i=0;$i<$total;$i++) {
   if ($parent_list[0]) {
    $res[$i]['PARENT_LIST']=implode(',', $parent_list);
   } else {
    $res[$i]['PARENT_LIST']='0';
   }
   $sub_list[]=$res[$i]['ID'];
   $tmp_parent=$parent_list;
   $tmp_parent[]=$res[$i]['ID'];
   $sub_this=$this->updateTree_commands($res[$i]['ID'], $tmp_parent);
   if ($sub_this[0]) {
    $res[$i]['SUB_LIST']=implode(',', $sub_this);
   } else {
    $res[$i]['SUB_LIST']=$res[$i]['ID'];
   }
   SQLUpdate($table, $res[$i]);
   $sub_list=array_merge($sub_list, $sub_this);
  }
  return $sub_list;
 }


  function processMenuElements(&$res) {

   startMeasure('processMenuElements');

   startMeasure('processMenuElements '.$_SERVER['REQUEST_URI']);

   if ($this->action!='admin') {
    $total=count($res);
    $res2=array();
    for($i=0;$i<$total;$i++) {
     if (checkAccess('menu', $res[$i]['ID'])) {
      $res2[]=$res[$i];
     }
    }
    $res=$res2;
    unset($res2);
   }

   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   if ($res[$i+1]['INLINE']) {
    $res[$i]['INLINE']=1;
   }



   $item=$res[$i];
   if ($item['VISIBLE_DELAY']) {
    $out['VISIBLE_DELAYS']++;
   }

   if ($item['EXT_ID'] && $this->action!='admin') {
    $visible_delay=$item['VISIBLE_DELAY'];
    $tmp=SQLSelectOne("SELECT * FROM commands WHERE ID='".(int)$item['EXT_ID']."'");
    if ($tmp['ID']) {
     $item=$tmp;
     $item['VISIBLE_DELAY']=$visible_delay;
     $res[$i]=$item;
    }
   } elseif ($item['EXT_ID'] && $this->action=='admin') {
    $tmp=SQLSelectOne("SELECT * FROM commands WHERE ID='".(int)$item['EXT_ID']."'");
    if ($tmp['ID']) {
     $item['TITLE']=$item['TITLE'].' ('.$tmp['TITLE'].')';
     $res[$i]=$item;
    }
   }

   if ($item['LINKED_PROPERTY']!='') {
    $lprop=getGlobal($item['LINKED_OBJECT'].'.'.$item['LINKED_PROPERTY']);
    if ($item['TYPE']=='custom') {
     $field='DATA';
    } else {
     $field='CUR_VALUE';
    }
    if ($lprop!=$item[$field]) {
     $item[$field]=$lprop;
     SQLUpdate('commands', $item);
     $res[$i]=$item;
    }
   }

   if ($item['TYPE']=='timebox') {

    $tmp=explode(':', $item['CUR_VALUE']);
    $value1=(int)$tmp[0];
    $value2=(int)$tmp[1];

    for($h=0;$h<=23;$h++) {
     $v=$h;
     if ($v<10) {
      $v='0'.$v;
     }
     $selected=0;
     if ($h==$value1) {
      $selected=1;
     }
     $item['OPTIONS1'][]=array('VALUE'=>$v, 'SELECTED'=>$selected);
    }
    for($h=0;$h<=59;$h++) {
     $v=$h;
     if ($v<10) {
      $v='0'.$v;
     }
     $selected=0;
     if ($h==$value2) {
      $selected=1;
     }
     $item['OPTIONS2'][]=array('VALUE'=>$v, 'SELECTED'=>$selected);
    }
    $res[$i]=$item;
   }


   if ($item['TYPE']=='selectbox') {
    $data=explode("\n", str_replace("\r", "", $item['DATA']));
    $item['OPTIONS']=array();
    foreach($data as $line) {
     $line=trim($line);
     if ($line!='') {
      $option=array();
      $tmp=explode('|', $line);
      $option['VALUE']=$tmp[0];
      if ($tmp[1]!='') {
       $option['TITLE']=$tmp[1];
      } else {
       $option['TITLE']=$option['VALUE'];
      }
      if ($option['VALUE']==$item['CUR_VALUE']) {
       $option['SELECTED']=1;
      }
      $item['OPTIONS'][]=$option;
     }
    }
    $res[$i]=$item;
   }

   if ($this->owner->name!='panel') {
    $res[$i]['TITLE']=processTitle($res[$i]['TITLE'], $this);
    if ($res[$i]['TYPE']=='custom') {
     $res[$i]['DATA']=processTitle($res[$i]['DATA'], $this);
    }
   }


    foreach($res[$i] as $k=>$v) {
     if (!is_array($res[$i][$k]) && $k!='DATA') {
      $res[$i][$k]=addslashes($v);
     }
    }

    $tmp=SQLSelectOne("SELECT COUNT(*) as TOTAL FROM commands WHERE PARENT_ID='".$res[$i]['ID']."'");
    if ($tmp['TOTAL']) {
     $res[$i]['RESULT_TOTAL']=$tmp['TOTAL'];
    }


    if ($res[$i]['SUB_PRELOAD'] && $this->action!='admin') {
     $children=SQLSelect("SELECT * FROM commands WHERE PARENT_ID='".$res[$i]['ID']."' ORDER BY PRIORITY DESC, TITLE");
     if ($children[0]['ID']) {
      $this->processMenuElements($children);
      if ($children[0]['ID']) {
       $res[$i]['RESULT']=$children;
      }
     }
    }


   }
   endMeasure('processMenuElements '.$_SERVER['REQUEST_URI'], 1);
   endMeasure('processMenuElements', 1);

  }

 /**
 * Title
 *
 * Description
 *
 * @access public
 */
  function getParents($parent_id) {

   if (!$parent_id) {
    return array();
   }

   $res=array();

   $rec=SQLSelectOne("SELECT * FROM commands WHERE ID='".$parent_id."'");

   if ($rec['PARENT_ID']) {
    $parents=$this->getParents($rec['PARENT_ID']);
    foreach($parents as $v) {
     $res[]=$v;
    }
   }

   $res[]=$rec;

   return $res;
  }

/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($parent_name="") {
  parent::install($parent_name);
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS commands');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
commands - Commands
*/
  $data = <<<EOD
 commands: ID int(10) unsigned NOT NULL auto_increment
 commands: TITLE varchar(255) NOT NULL DEFAULT ''
 commands: SYSTEM varchar(255) NOT NULL DEFAULT ''
 commands: COMMAND varchar(255) NOT NULL DEFAULT ''
 commands: URL varchar(255) NOT NULL DEFAULT ''
 commands: TYPE char(50) NOT NULL DEFAULT ''
 commands: WINDOW varchar(255) NOT NULL DEFAULT ''
 commands: WIDTH int(10) NOT NULL DEFAULT '0'
 commands: HEIGHT int(10) NOT NULL DEFAULT '0'
 commands: PARENT_ID int(10) NOT NULL DEFAULT '0'
 commands: PRIORITY int(10) NOT NULL DEFAULT '0'
 commands: MIN_VALUE int(10) NOT NULL DEFAULT '0'
 commands: MAX_VALUE int(10) NOT NULL DEFAULT '0'
 commands: CUR_VALUE varchar(255) NOT NULL DEFAULT '0'
 commands: STEP_VALUE int(10) NOT NULL DEFAULT '1'
 commands: DATA text
 commands: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 commands: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
 commands: EXT_ID int(10) NOT NULL DEFAULT '0'
 commands: VISIBLE_DELAY int(10) NOT NULL DEFAULT '0'
 commands: INLINE int(3) NOT NULL DEFAULT '0'
 commands: SUB_PRELOAD int(3) NOT NULL DEFAULT '0'

 commands: ONCHANGE_OBJECT varchar(255) NOT NULL DEFAULT ''
 commands: ONCHANGE_METHOD varchar(255) NOT NULL DEFAULT ''
 commands: SCRIPT_ID int(10) NOT NULL DEFAULT '0'
 commands: ICON varchar(50) NOT NULL DEFAULT ''
 commands: CODE text


 commands: SUB_LIST text
 commands: PARENT_LIST text
 commands: AUTOSTART int(3) NOT NULL DEFAULT '0'
 commands: AUTO_UPDATE int(10) NOT NULL DEFAULT '0'
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDA5LCAyMDA5IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
?>