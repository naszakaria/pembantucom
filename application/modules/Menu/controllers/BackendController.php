<?php
//Menu Backend Controller Class
class Menu_BackendController extends Zend_Controller_Action
{
	private $menuForm;
	private $menuGroupForm;
	private $_controllerCache;
	private $uploadForm;
	
    public function init()
    {
        /* Initialize action controller here */
		$this->menuForm =  new Menu_Form_MenuForm ();
		$this->menuGroupForm =  new Menu_Form_MenuGroupForm ();
		
		//Initialize Cache
		$cache = new Eicra_View_Helper_Cache();
		$this->_controllerCache = 	$cache->getCache();
    }
	
	public function preDispatch() 
	{
		
		$this->_helper->layout->setLayout('layout');
		$this->_helper->layout->setLayoutPath(MODULE_PATH.'/Administrator/layouts/scripts');
		
		
		$translator = Zend_Registry::get('translator');
		$this->view->assign('translator', $translator);
		$this->view->setEscape('stripslashes');	
			
		$getAction = $this->_request->getActionName();
		$this->view->assign('getAction', $getAction);
		$getController = $this->_request->getControllerName();	
		$this->view->assign('getController', $getController);
		
		if($getAction != 'uploadfile')
		{	
			$url = Zend_Registry::get('config')->eicra->params->domain.$this->view->baseUrl().'/Administrator/login';
			Eicra_Global_Variable::checkSession($this->_response,$url);	
		}
	}
	
	//MENU LIST FUNCTION

    public function listAction()
    {		
		// action body
		$pageNumber = $this->getRequest()->getParam('page');		
		$getViewPageNum = $this->getRequest()->getParam('viewPageNum'); 		
		$viewPageNumSes = Eicra_Global_Variable::getSession()->viewPageNum;
		$draft_menu = $this->getRequest()->getParam('draft');	
		
		if(empty($getViewPageNum) && empty($viewPageNumSes))
		{
			$viewPageNum = '100';
		}
		else if(!empty($getViewPageNum) && empty($viewPageNumSes))
		{
			$viewPageNum = $getViewPageNum;
		}
		else if(empty($getViewPageNum) && !empty($viewPageNumSes))
		{
			$viewPageNum = $viewPageNumSes;
		}
		else if(!empty($getViewPageNum) && !empty($viewPageNumSes))
		{
			$viewPageNum = $getViewPageNum;
		}
		
		$auth = Zend_Auth::getInstance ();	
		$user_id = ($auth->hasIdentity ()) ? $auth->getIdentity()->user_id : '' ;
		
		$where = '';
		if ($this->_request->getParams()) 
		{
			$params = $this->_request->getParams();
		
			unset($params['page']);
			unset($params['module']);
			unset($params['controller']);
			unset($params['action']);
			unset($params['menu_id']);
			unset($params['viewPageNum']);
			unset($params['_route_']);
				
			if(!empty($params))
			{
				$i = 0;
				foreach($params as $key => $value)
				{
					
					if($key == 'menu_name')
					{
						if($i == 0)
						{
							$where .= (!empty($value) && strtolower($value) != 'any') ? ' m.'.$key.' LIKE "'.$value.'%"' : '1=1';	
						}
						else
						{
							$where .= (!empty($value) && strtolower($value) != 'any') ? ' AND m.'.$key.' LIKE "'.$value.'%"' : ' AND 1=1';	
						}
					}
					else
					{
						if($i == 0)
						{
							$where .= (!empty($value) && strtolower($value) != 'any') ? ' m.'.$key.' = '.$value : '1=1';	
						}
						else
						{
							$where .= (!empty($value) && strtolower($value) != 'any') ? ' AND m.'.$key.' = '.$value : ' AND 1=1';	
						}
					}
					$i++;			
				}
			}
			
			Eicra_Global_Variable::getSession()->viewPageNum = $viewPageNum;
			$uniq_id = preg_replace('/[^a-zA-Z0-9_]/','_',$this->view->url().$where.implode('_',$params)).'_'.$viewPageNum.'_'.$user_id;
			if( ($menu_datas = $this->_controllerCache->load($uniq_id)) === false ) 
			{		
				$menus = new Menu_Model_MenuListMapper();			
				$menu_datas =  $menus->fetchAll($pageNumber,$draft_menu, $where);
				$this->_controllerCache->save($menu_datas, $uniq_id);
			}
			$this->view->postData = $params;
		}
		else
		{					
			Eicra_Global_Variable::getSession()->viewPageNum = $viewPageNum;
			$uniq_id = preg_replace('/[^a-zA-Z0-9_]/','_',$this->view->url()).'_'.$viewPageNum.'_'.$user_id;
			if( ($menu_datas = $this->_controllerCache->load($uniq_id)) === false ) 
			{		
				$menus = new Menu_Model_MenuListMapper();			
				$menu_datas =  $menus->fetchAll($pageNumber,$draft_menu);
				$this->_controllerCache->save($menu_datas, $uniq_id);
			}
		}
		$this->view->draft_menu	=	$draft_menu;
		$this->view->menu_datas	=	$menu_datas;	
    }
	
	//MENU GROUP LIST FUNCTION
	
	public function listgrpAction()
    {		
		// action body
		$pageNumber = $this->getRequest()->getParam('page');		
		$getViewPageNum = $this->getRequest()->getParam('viewPageNum'); 		
		$viewPageNumSes = Eicra_Global_Variable::getSession()->viewPageNum;
		
		if(empty($getViewPageNum) && empty($viewPageNumSes))
		{
			$viewPageNum = '100';
		}
		else if(!empty($getViewPageNum) && empty($viewPageNumSes))
		{
			$viewPageNum = $getViewPageNum;
		}
		else if(empty($getViewPageNum) && !empty($viewPageNumSes))
		{
			$viewPageNum = $viewPageNumSes;
		}
		else if(!empty($getViewPageNum) && !empty($viewPageNumSes))
		{
			$viewPageNum = $getViewPageNum;
		}
					
		Eicra_Global_Variable::getSession()->viewPageNum = $viewPageNum;
		$uniq_id = preg_replace('/[^a-zA-Z0-9_]/','_',$this->view->url()).'_'.$viewPageNum;
		if( ($menu_group_datas = $this->_controllerCache->load($uniq_id)) === false ) 
		{			
			$menuGroup = new Menu_Model_MenuGroupListMapper();			
			$menu_group_datas =  $menuGroup->fetchAll($pageNumber);		
			$this->_controllerCache->save($menu_group_datas, $uniq_id);		
		}
		$this->view->menu_group_datas	=	$menu_group_datas;	
		$this->view->menuGroupForm = $this->menuGroupForm;		
    }
	
	//MENU FUNCTIONS
	
	public function parentAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			
		$id = substr($this->_getParam('id'), 5);
		$parent_id = substr($this->_getParam('to'), 5);
		
		if($parent_id != '' && !empty($id))
		{
			//DB Connection
			$conn = Zend_Registry::get('msqli_connection');
			$conn->getConnection();
			
			// Update Menu Parent
			$where = array();
			$where[] = 'menu_id = '.$conn->quote($id);
			$return = $conn->update(Zend_Registry::get('dbPrefix').'menu',array('parent' => $parent_id), $where);
			
			if($return)
			{
				$menuOrdersObj = new Menu_Controller_Helper_MenuOrders($id);
				$menuOrdersObj->setNewOrder($id); 			
				$json_arr = array('status' => 'ok','id' => $id,'parent_id' => $parent_id);
			}
			else
			{
				$msg = $translator->translator('menu_change_parent_err');	
				$json_arr = array('status' => 'err','msg' => $msg);
			}
			
			//Convert To JSON ARRAY	
			$res_value = Zend_Json::encode($json_arr);	
			$this->_response->setBody($res_value);
		}
	}
	
	public function groupmenuAction()
	{		
		if ($this->_request->isPost()) 
		{
			$group_id = $this->_request->getPost('grp_id');
			$parent = $this->_request->getPost('parent');
			
			$groupMenu = Menu_View_Helper_Menutree::getSubMenu('0',$this->view,$group_id,$parent);
			
			$this->_helper->viewRenderer->setNoRender();
			$this->_helper->layout->disableLayout();
			$translator = Zend_Registry::get('translator');
			
			if($groupMenu)
			{			
				$json_arr = array('status' => 'ok','groupMenu' => $groupMenu);
			}
			else
			{
				$msg = $translator->translator('menu_groug_err');	
				$json_arr = array('status' => 'err','msg' => $msg);
			}	
			//Convert To JSON ARRAY	
			$res_value = Zend_Json::encode($json_arr);	
			$this->_response->setBody($res_value);
		}
	}
	
	public function layoutsAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{
			$module_name = $this->_request->getPost('module_name');
			if($module_name)
			{
				$layouts = Menu_View_Helper_ModuleLoader::getLayouts($module_name);
				$json_arr = array('status' => 'ok','layouts' => $layouts);
			}
			else
			{
				$msg = $translator->translator('menu_module_found_err');	
				$json_arr = array('status' => 'err','msg' => $msg);
			}
		}
		else
		{
			$msg = $translator->translator('menu_request_post_err');	
			$json_arr = array('status' => 'err','msg' => $msg);
		}
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
	}
	
	public function getpagesAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{
			$layout_name = explode(',',$this->_request->getPost('layout_name'));
			if($layout_name[2])
			{
				$dbTable = Menu_View_Helper_ModuleLoader::getPages($layout_name[0],$layout_name[1],$layout_name[2]);
				if($dbTable[0]['table'])
				{
					//DB Connection
					$conn = Zend_Registry::get('msqli_connection');
					$conn->getConnection();
					
					//Get Pages
					$order = 'tb.'.$dbTable[0]['fields'][0].' ASC';
					$select = $conn->select()
									->from(array('tb' => Zend_Registry::get('dbPrefix').$dbTable[0]['table']), $dbTable[0]['fields'])
									->order($order);
					$rs = $conn->fetchAll($select);
					$pageRes = array();
					$fieldNo = count($dbTable[0]['fields']);
					if($rs)
					{
						$k=0;
						foreach($rs as $datas)
						{
							for($j = 0;$j < $fieldNo; $j++)
							{
								$pageRes[$k][$j] = stripslashes($datas[$dbTable[0]['fields'][$j]]);
							}
							$k++;
						}
					}			
					$json_arr = array('status' => 'ok','pageRes' => $pageRes);
				}
				else
				{
					$msg = $translator->translator('menu_layout_found_err');
					$json_arr = array('status' => 'err','pageRes' => 'noPage','msg' => $msg);
				}
			}
			else
			{
				$msg = $translator->translator('menu_layout_found_err');	
				$json_arr = array('status' => 'err','msg' => $msg);
			}
		}
		else
		{
			$msg = $translator->translator('menu_request_post_err');	
			$json_arr = array('status' => 'err','msg' => $msg);
		}
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
	}
	
	public function typesAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');		
		//Get All Modules
		$module_loader = new Menu_View_Helper_ModuleLoader();
		$modules = $module_loader->getModules();
		if($modules)
		{		
			$layout_obj = array();
			$i= 0;
			foreach($modules as $module_name)
			{
							
				$layouts = $module_loader->getLayouts($module_name);
				$layouts_array = $this->getPermittedLayout($layouts, $module_name);
				if(is_array($layouts_array) && (count($layouts_array) > 0))
				{
					shuffle($layouts_array);
					$layout_obj[$i]['module'] = $module_name;	
					$layout_obj[$i]['layouts'] = 	$layouts_array;
					$i++;					
				}
			}	
			$json_arr = array('status' => 'ok', 'layout_obj' => $layout_obj);
		}
		else
		{
			$msg = $translator->translator('menu_module_not_found');
			$json_arr = array('status' => 'err', 'msg' => $msg);
		}
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
	}
	
	private function getPermittedLayout($layout_arr, $module_name)
	{
		foreach($layout_arr as $key => $array)
		{
			if($array['name'])
			{			
				$name_arr = explode(',', $array['name']);	
				if(!empty($name_arr[0]) && !empty($name_arr[1]))
				{						
					try
					{
						if(!$this->view->allow($name_arr[1], $name_arr[0], $module_name))
						{
							unset($layout_arr[$key]);
						}
					}
					catch(Exception $e)
					{
						unset($layout_arr[$key]);
					}
				}
			}
		}
		return $layout_arr;
	}

	public function addAction()
	{		
		$translator = Zend_Registry::get('translator');
		if ($this->_request->isPost()) 
		{
			$this->_helper->viewRenderer->setNoRender();
			$this->_helper->layout->disableLayout();	
			
			if($this->menuForm->isValid($this->_request->getPost())) 
			{
				$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
				$auth = Zend_Auth::getInstance ();
				$stat = ($auth->hasIdentity () && $auth->getIdentity ()->auto_publish_article == '1') ? '1' : '0';
				$menus = new Menu_Model_Menus($this->menuForm->getValues());
				$menus->setMenu_status($stat);
				$menus->setMenu_default('0');
				$menus->setExternal_link($this->_request->getPost('external_link'));				
				
				$perm = new Menu_View_Helper_Allow();
				if($perm->allow())
				{
					$result = $menus->saveMenu();
					if($result['status'] == 'ok')
					{
						$last_id = $result['id'];
						$select_layout = explode(',',$this->_request->getPost('select_layout'));
						$module_name = $select_layout[0];
						$controller_name = $select_layout[1];
						$action_name = $select_layout[2];
						$page_id_arr = $this->_request->getPost('select_page');	
						$page_id = '';
						if(is_array($page_id_arr))
						{					
							$page_id = implode(',',$page_id_arr);
						}
						else
						{
							$page_id = $page_id_arr;
						}
												
						try
						{
							//DB Connection
							$conn = Zend_Registry::get('msqli_connection');
							$conn->getConnection();
							
							//Delete Data FROM menu_assign Table
							$where = array();
							$where[] = 'menu_id = '.$conn->quote($last_id);
							$conn->delete(Zend_Registry::get('dbPrefix').'menu_assign', $where);
							
							$msg = $translator->translator("menu_save_success");
							$json_arr = array('status' => 'ok','msg' => $msg);
						}
						catch(Exception $e)
						{
							$json_arr = array('status' => 'err','msg' => $e->getMessage());
						}
						
						if(!empty($module_name) && !empty($controller_name) && !empty($action_name))
						{
							try
							{
								//Insert Query
								$rs = $conn->insert(Zend_Registry::get('dbPrefix').'menu_assign', 
													array(
													'menu_id' => $last_id,
													'module_name' => $module_name,
													'controller_name' => $controller_name,
													'action_name' => $action_name,
													'page_id' => $page_id
													)
											 );							
								$msg = $translator->translator("menu_save_success");
								$json_arr = array('status' => 'ok','msg' => $msg);
							}
							catch(Exception $e)
							{
								$msg = $translator->translator("menu_save_success").$translator->translator("menu_assigned_err").' '.$e->getMessage();
								$json_arr = array('status' => 'err','msg' => $msg);
							}
						}
					}
					else
					{
						$msg = $translator->translator("menu_save_err");
						$json_arr = array('status' => 'err','msg' => $msg.' '.$result['msg']);
					}	
				}
				else
				{
					$Msg =  $translator->translator("menu_add_action_deny_desc");
					$json_arr = array('status' => 'errP','msg' => $Msg);
				}
			
			}
			else
			{
				$validatorMsg = $this->menuForm->getMessages();
				$vMsg = array();
				$i = 0;
				foreach($validatorMsg as $key => $errType)
				{					
					foreach($errType as $errkey => $value)
					{
						$vMsg[$i] = array('key' => $key, 'errKey' => $errkey, 'value' => $value);
						$i++;
					}
				}
				$json_arr = array('status' => 'errV','msg' => $vMsg);
			}			
			$res_value = Zend_Json::encode($json_arr);			
			$this->_response->setBody($res_value);
		}
		else
		{	
			$articles_db = new Articles_Model_DbTable_ArticleList();
			$first_article = $articles_db->getFirstArticle();
			$article_id = ($this->_request->getParam('article_id')) ? $this->_request->getParam('article_id') : $first_article['article_id'];
			if(!empty($article_id))
			{				
				$article_info = $articles_db->getArticleInfo($article_id) ;			
				$this->view->article_info = $article_info;
				
				$this->menuForm->module->setValue('Articles');
				$this->menuForm->select_layout->setValue('Articles,frontend,view,articles');
				$this->menuForm->menu_type->setValue($translator->translator('permisson_article_front_view',null,'Articles'));
			}
			$this->view->menuForm = $this->menuForm;			
			$this->render();
		}	
	}	
	
	public function editAction()
	{
		$menu_id = $this->_getParam('menu_id', 0);
		
		//DB Connection
		$conn = Zend_Registry::get('msqli_connection');
		$conn->getConnection();
		$translator = Zend_Registry::get('translator');
		
		//GET DATA
		$menuData = new Menu_Model_DbTable_Menu();
		
		if ($this->_request->isPost()) 
		{
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			$this->_helper->viewRenderer->setNoRender();
			$this->_helper->layout->disableLayout();			
			
			if($this->menuForm->isValid($this->_request->getPost())) 
			{	
				$menus = new Menu_Model_Menus($this->menuForm->getValues());								
				
				$perm = new Menu_View_Helper_Allow();
				if($perm->allow())
				{
					$menu_id = $this->_request->getPost('menu_id');
					if($menu_id)
					{						
						$parent_id = $this->_request->getPost('parent');
						if($menu_id != $parent_id)
						{
							$menuOptions = new Menu_Model_DbTable_Menu();
							$idString = $menuOptions->getAllSubMenuId($menu_id);
							$id_arr = explode(',',$idString);				
							if(!in_array($parent_id, $id_arr,true))
							{
								$menus->setMenu_id($menu_id);
								$menus->setMenu_title($this->_request->getPost('menu_title'),$menu_id);
								if($this->_request->getPost('select_layout') == 'ext')
								{
									$menus->setExternal_link($this->_request->getPost('external_link'));
								}
								else
								{
									$menus->setExternal_link('');
								}	
								$menuInfo = $menuData->getMenuInfo($menu_id);
								$menus->setPrev_parent($menuInfo['parent']);
								$menus->setPrev_group($menuInfo['group_id']);												
								$result = $menus->saveMenu();
								if($result['status'] == 'ok')
								{
									$menuInfo = $menuData->getMenuInfo($menu_id);
									$select_layout = explode(',',$this->_request->getPost('select_layout'));
									$module_name = $select_layout[0];
									$controller_name = $select_layout[1];
									$action_name = $select_layout[2];
									$page_id_arr = $this->_request->getPost('select_page');	
									$page_id = '';
									if(is_array($page_id_arr))
									{					
										$page_id = implode(',',$page_id_arr);
									}
									else
									{
										$page_id = $page_id_arr;
									}
									//DB Connection
									$conn = Zend_Registry::get('msqli_connection');
									$conn->getConnection();
									
									try
									{
										//Delete Data FROM menu_assign Table
										$where = array();
										$where[] = 'menu_id = '.$conn->quote($menu_id);
										$conn->delete(Zend_Registry::get('dbPrefix').'menu_assign', $where);
										
										$msg = $translator->translator("menu_save_success");
										$json_arr = array('status' => 'ok','msg' => $msg, 'menuInfo' => $menuInfo);
									}
									catch(Exception $e)
									{
										$msg = $e->getMessage();
										$json_arr = array('status' => 'err','msg' => $msg);
									}
									
									if(!empty($module_name) && !empty($controller_name) && !empty($action_name))
									{
										try
										{
											//Insert Query
											$rs = $conn->insert(Zend_Registry::get('dbPrefix').'menu_assign', 
																array(
																'menu_id' => $menu_id,
																'module_name' => $module_name,
																'controller_name' => $controller_name,
																'action_name' => $action_name,
																'page_id' => $page_id
																)
														 );
										
											$msg = $translator->translator("menu_save_success");
											$json_arr = array('status' => 'ok','msg' => $msg, 'menuInfo' => $menuInfo);
										}
										catch(Exception $e)
										{
											$msg = $translator->translator("menu_save_success").$translator->translator("menu_assigned_err").' '.$e->getMessage();
											$json_arr = array('status' => 'err','msg' => $msg);
										}
									}
								}
								else
								{
									$msg = $translator->translator("menu_save_err").' '.$result['msg'];
									$json_arr = array('status' => 'err','msg' => $msg);
								}	
							}
							else
							{
								$Msg =  $translator->translator("menu_child_not_parent");
								$json_arr = array('status' => 'err','msg' => $Msg);
							}							
						}
						else
						{
							$Msg =  $translator->translator("menu_same_parent_desc");
							$json_arr = array('status' => 'err','msg' => $Msg);
						}
					}
					else
					{
						$msg =  $translator->translator("menu_id_not_found");
						$json_arr = array('status' => 'errP','msg' => $msg);
					}
				}
				else
				{
					$Msg =  $translator->translator("menu_add_action_deny_desc");
					$json_arr = array('status' => 'errP','msg' => $Msg);
				}
			
			}
			else
			{
				$validatorMsg = $this->menuForm->getMessages();
				$vMsg = array();
				$i = 0;
				foreach($validatorMsg as $key => $errType)
				{					
					foreach($errType as $errkey => $value)
					{
						$vMsg[$i] = array('key' => $key, 'errKey' => $errkey, 'value' => $value);
						$i++;
					}
				}
				$json_arr = array('status' => 'errV','msg' => $vMsg);
			}			
			$res_value = Zend_Json::encode($json_arr);			
			$this->_response->setBody($res_value);			
		}
		else
		{			
			$menuInfo = $menuData->getMenuInfo($menu_id);
			$group_id = $menuInfo['group_id'];
			$parent = $menuInfo['parent'];
			$external_link	= $menuInfo['external_link'];
			if($external_link){ $menuInfo['module'] = 'ext';$menuInfo['select_layout'] = 'ext'; $menuInfo['menu_type'] = $translator->translator("menu_system_external"); }
			if($parent == '0')
			{
				$selected = 'style="background-color:#D7D7D7"';
				$parent_name = $translator->translator("menu_page_menu_root");
			}
			else
			{
				$select = $conn->select()->from(array('m' => Zend_Registry::get('dbPrefix').'menu'), array('m.menu_name'))->where('menu_id = ?',$parent);
				$rs = $select->query()->fetchAll();
				foreach($rs as $row)
				{
					$parent_name = $row['menu_name'];
				}
				$selected = '';
			}
			
			//Get Data From Menu Assign
			$selectMenuData = $conn->select()->from(array('ma' => Zend_Registry::get('dbPrefix').'menu_assign'), array('ma.id','ma.module_name','ma.controller_name','ma.action_name','ma.page_id'))->where('ma.menu_id = ?',$menu_id);
			$rsMenu = $selectMenuData->query()->fetchAll();
			if($rsMenu)
			{
				foreach($rsMenu as $row)
				{
					$module_name = $row['module_name'];
					$controller_name = $row['controller_name'];
					$action_name = $row['action_name'];
					$page_id = $row['page_id'];
				}
					
				$menuInfo['module'] = $module_name;
												
				$layout_info = new Menu_Controller_Helper_Layouts($module_name,$controller_name.','.$action_name); 
				$menuInfo['select_layout'] = $layout_info->_select_layout;
				$menuInfo['menu_type'] 	= $layout_info->_menu_type;
				if(!empty($page_id))
				{
					$page_combo =  new Menu_Controller_Helper_Pages($module_name,$controller_name,$action_name,$page_id);
					$this->view->page_combo = $page_combo;
				}
			}
			
			$this->view->menuTree = '<table class="example" id="dnd-example">'.
										'<tbody ><tr id="node-0">'.
										 '<td '.$selected.'>'.
											'<span class="folder">'.$translator->translator("menu_page_menu_root").'</span>'.
										'</td>'.
									'</tr>'.Menu_View_Helper_Menutree::getSubMenu('0',$this->view,$group_id,$parent).'</tbody></table>';
						
			$this->menuForm->populate($menuInfo);				
			$this->view->menu_id = $menu_id;
			$this->view->parent_id = $parent;
			$this->view->entry_by	=	$menuInfo['entry_by'];
			$this->view->external_link = $external_link;
			$this->view->parent_name = $parent_name;
			$this->view->menuForm = $this->menuForm;	
			$this->render();
		}	
	}
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{	
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			$menu_id = $this->_request->getPost('menu_id');
			$menu_name = $this->_request->getPost('menu_name');
						
			//DB Connection
			$conn = Zend_Registry::get('msqli_connection');
			$conn->getConnection();
			
			//Check Menu Default
			$selectDefault = $conn->select()
							->from(array('m' => Zend_Registry::get('dbPrefix').'menu'), array('m.menu_default'))
							->where('m.menu_id = ?',$menu_id);
			$rs_default = $selectDefault->query()->fetchAll();
			if($rs_default)
			{				
				foreach($rs_default as $row)
				{
					$menu_default = $row['menu_default'];
				}
			}				
			if($menu_default != '1')
			{
				//Get It's Child order
				$menuOrdersObj = new Menu_Controller_Helper_MenuOrders($menu_id);
				$parent_id = 	$menuOrdersObj->getParent();		
				$select = $conn->select()
							->from(array('m' => Zend_Registry::get('dbPrefix').'menu'), array('m.menu_id'))
							->where('m.parent = ?',$menu_id);
				$rs = $select->query()->fetchAll();
				if($rs)
				{				
					foreach($rs as $row)
					{
						$sub_menu_id = (int)$row['menu_id'];
						$menuOrdersObj->setNewOrder($sub_menu_id); 
					}
				}
				
				//update parents of it's child
				$whereP = array();
				$whereP[] = 'parent = '.$conn->quote($menu_id);
				$conn->update(Zend_Registry::get('dbPrefix').'menu',array('parent' => $parent_id),$whereP);
				
				//Delete Assigned Menu
				$whereA = array();
				$whereA[] = 'menu_id = '.$conn->quote($menu_id);
				$conn->delete(Zend_Registry::get('dbPrefix').'menu_assign', $whereA);
				
				// Remove from Menus
				$where = array();
				$where[] = 'menu_id = '.$conn->quote($menu_id);
				try
				{
					$conn->delete(Zend_Registry::get('dbPrefix').'menu', $where);			
					$msg = 	$translator->translator('menu_list_delete_success',$menu_name);			
					$json_arr = array('status' => 'ok','msg' => $msg);
				}
				catch (Exception $e) 
				{
					$msg = 	$translator->translator('menu_list_delete_err');			
					$json_arr = array('status' => 'err','msg' => $msg);
				}
			}
			else
			{
				$msg = 	$translator->translator('menu_list_delete_default_err',$menu_name);			
				$json_arr = array('status' => 'err','msg' => $msg);
			}
		}
		else
		{
			$msg = 	$translator->translator('menu_list_delete_err');			
			$json_arr = array('status' => 'err','msg' => $msg);
		}
			//Convert To JSON ARRAY	
			$res_value = Zend_Json::encode($json_arr);	
			$this->_response->setBody($res_value);
	}
	
	public function deleteallAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);	
			$menu_id_str = $this->_request->getPost('menu_id_st');
			$perm = new Menu_View_Helper_Allow();			
			if ($perm->allow('delete','backend','Menu'))
			{
				if(!empty($menu_id_str))
				{	
					$menu_id_arr = explode(',',$menu_id_str);		
					//DB Connection
					$conn = Zend_Registry::get('msqli_connection');
					$conn->getConnection();
					
					$non_del_arr = array();
					$k = 0;
					foreach($menu_id_arr as $menu_id)
					{
						//Check Menu Default
						$selectDefault = $conn->select()
										->from(array('m' => Zend_Registry::get('dbPrefix').'menu'), array('m.menu_default'))
										->where('m.menu_id = ?',$menu_id);
						$rs_default = $selectDefault->query()->fetchAll();
						if($rs_default)
						{				
							foreach($rs_default as $row)
							{
								$menu_default = $row['menu_default'];
							}
						}				
						if($menu_default != '1')
						{
							//Get It's Child order
							$menuOrdersObj = new Menu_Controller_Helper_MenuOrders($menu_id);
							$parent_id = 	$menuOrdersObj->getParent();		
							$select = $conn->select()
										->from(array('m' => Zend_Registry::get('dbPrefix').'menu'), array('m.menu_id'))
										->where('m.parent = ?',$menu_id);
							$rs = $select->query()->fetchAll();
							if($rs)
							{				
								foreach($rs as $row)
								{
									$sub_menu_id = (int)$row['menu_id'];
									$menuOrdersObj->setNewOrder($sub_menu_id); 
								}
							}
							
							//update parents of it's child
							$whereP = array();
							$whereP[] = 'parent = '.$conn->quote($menu_id);
							$conn->update(Zend_Registry::get('dbPrefix').'menu',array('parent' => $parent_id),$whereP);
							
							//Delete Assigned Menu
							$whereA = array();
							$whereA[] = 'menu_id = '.$conn->quote($menu_id);
							$conn->delete(Zend_Registry::get('dbPrefix').'menu_assign', $whereA);
							
							// Remove from Menus
							$where = array();
							$where[] = 'menu_id = '.$conn->quote($menu_id);
							try
							{
								$conn->delete(Zend_Registry::get('dbPrefix').'menu', $where);
								$msg = 	$translator->translator('menu_list_delete_success');			
								$json_arr = array('status' => 'ok','msg' => $msg, 'non_del_arr' => $non_del_arr);
							}
							catch (Exception $e) 
							{
								$non_del_arr[$k] = $menu_id;
								$k++;
								$msg = 	$translator->translator('menu_list_delete_err');			
								$json_arr = array('status' => 'err','msg' => $msg, 'non_del_arr' => $non_del_arr);
							}
						}
						else
						{
							$non_del_arr[$k] = $menu_id;
							$k++;
							$msg = 	$translator->translator('menu_list_delete_err');			
							$json_arr = array('status' => 'err','msg' => $msg, 'non_del_arr' => $non_del_arr);
						}
					}
				}
				else
				{
					$msg = $translator->translator("menu_selected_err");
					$json_arr = array('status' => 'err','msg' => $msg);
				}
			}
			else
			{
				$msg = 	$translator->translator('menu_delete_perm');			
				$json_arr = array('status' => 'err','msg' => $msg);
			}
		}
		else
		{
			$msg = 	$translator->translator('menu_list_delete_err');			
			$json_arr = array('status' => 'err','msg' => $msg);
		}
			//Convert To JSON ARRAY	
			$res_value = Zend_Json::encode($json_arr);	
			$this->_response->setBody($res_value);
	}
	
	public function publishAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{	
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			$menu_id = $this->_request->getPost('menu_id');
			$menu_name = $this->_request->getPost('menu_name');
			$paction = $this->_request->getPost('paction');
			
			switch($paction)
			{
				case 'publish':
					$menu_status = '1';
					break;
				case 'unpublish':
					$menu_status = '0';
					break;
			}
			
			//DB Connection
			$conn = Zend_Registry::get('msqli_connection');
			$conn->getConnection();
			
			// Update Article status
			$where = array();
			$where[] = 'menu_id = '.$conn->quote($menu_id);
					
			try
			{	
				$conn->update(Zend_Registry::get('dbPrefix').'menu',array('menu_status' => $menu_status), $where);		
				$json_arr = array('status' => 'ok','menu_status' => $menu_status);
			}
			catch (Exception $e) 
			{
				$msg = $translator->translator('menu_list_publish_err',$menu_name);	
				$json_arr = array('status' => 'err','msg' => $msg);
			}
		}
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
			
	}
	
	public function publishallAction()
	{
		$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{	
			$menu_id_str = $this->_request->getPost('menu_id_st');
			$paction = $this->_request->getPost('paction');
			
			switch($paction)
			{
				case 'publish':
					$menu_status = '1';
					break;
				case 'unpublish':
					$menu_status = '0';
					break;
			}
			
			if(!empty($menu_id_str))
			{
				$menu_id_arr = explode(',',$menu_id_str);
				
				//DB Connection
				$conn = Zend_Registry::get('msqli_connection');
				$conn->getConnection();
				
				foreach($menu_id_arr as $menu_id)
				{
					// Update Article status
					$where = array();
					$where[] = 'menu_id = '.$conn->quote($menu_id);
					try
					{
						$conn->update(Zend_Registry::get('dbPrefix').'menu',array('menu_status' => $menu_status), $where);
						$return = true;
					}
					catch (Exception $e) 
					{
						$return = true;
					}
				}
			
				if($return)
				{			
					$json_arr = array('status' => 'ok');
				}
				else
				{
					$msg = $translator->translator('menu_list_publish_err');	
					$json_arr = array('status' => 'err','msg' => $msg);
				}
			}
			else
			{
				$msg = $translator->translator("menu_selected_err");
				$json_arr = array('status' => 'err','msg' => $msg);
			}
		}
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
			
	}
			
	public function primaryAction()
	{
		$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		$menu_id = $this->_request->getPost('menu_id');
		$menu_name = $this->_request->getPost('menu_name');
		
		//DB Connection
		$conn = Zend_Registry::get('msqli_connection');
		$conn->getConnection();
		
		
		$validatorMenuLink = new Zend_Validate_Db_RecordExists(
			array(
				'table' => Zend_Registry::get('dbPrefix').'menu_assign',
				'field' => 'menu_id'
			)
		);
				
		if ($validatorMenuLink->isValid($menu_id)) 
		{
			try 
			{
				$conn->update(Zend_Registry::get('dbPrefix').'menu',array('menu_default' => '0'));
				// Update Article status
				$where = array();
				$where[] = 'menu_id = '.$conn->quote($menu_id);
				try 
				{
					$conn->update(Zend_Registry::get('dbPrefix').'menu',array('menu_default' =>'1'), $where);
					$json_arr = array('status' => 'ok');
				}
				catch (Exception $e) 
				{
					$msg = $translator->translator('menu_list_default_err',$menu_name);	
					$json_arr = array('status' => 'err','msg' => $msg." ".$e->getMessage());	
				}					
			}
			catch (Exception $e)
			{
				$msg = $translator->translator('menu_list_default_err',$menu_name);	
				$json_arr = array('status' => 'err','msg' => $msg." ".$e->getMessage());	
			}
		}
		else
		{
			$msg = $translator->translator('menu_list_default_link_err',$menu_name);	
			$json_arr = array('status' => 'err','msg' => $msg);
		}
						
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
	}
	
	public function upAction()
	{
		$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		
		$menu_id = $this->_request->getPost('menu_id');
		$menu_order = $this->_request->getPost('menu_order');
				
				
		$menuOrderObj = new Menu_Controller_Helper_MenuOrders($menu_id);
		$returnV =	$menuOrderObj->decreaseMenuOrder();
		if($returnV['status'] == 'err')
		{
			$json_arr = array('status' => 'err','id_arr' => $returnV['id_arr'], 'msg' => $returnV['msg']);
		}
		else
		{
			$json_arr = array('status' => 'ok','id_arr' => $returnV['id_arr'], 'msg' => $returnV['msg']);
		}						
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
	}
	
	public function downAction()
	{
		$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
			
		
		$menu_id = $this->_request->getPost('menu_id');
		$menu_order = $this->_request->getPost('menu_order');
						
		$menuOrderObj = new Menu_Controller_Helper_MenuOrders($menu_id);
		$returnV =	$menuOrderObj->increaseMenuOrder();
		if($returnV['status'] == 'err')
		{
			$json_arr = array('status' => 'err','id_arr' => $returnV['id_arr'], 'msg' => $returnV['msg']);
		}
		else
		{
			$json_arr = array('status' => 'ok','id_arr' => $returnV['id_arr'], 'msg' => $returnV['msg']);
		}						
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
	}
	
	public function orderallAction()
	{
		$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');	
		
		if ($this->_request->isPost()) 
		{
			$menu_id_str = $this->_request->getPost('menu_id_arr');
			$menu_order_str = $this->_request->getPost('menu_order_arr');			
			
			$menu_id_arr = explode(',',$menu_id_str);
			$menu_order_arr = explode(',',$menu_order_str);
			
			$order_numbers = new Administrator_Controller_Helper_GlobalOrdersNumbers();
			$checkOrder = $order_numbers->checkOrderNumbers($menu_order_arr);
			if( $checkOrder['status'] == 'err')
			{
				$json_arr = array('status' => 'err','msg' => $checkOrder['msg']);
			}
			else
			{
				//Save Menu Order
				$i = 0;
				foreach($menu_id_arr as $menu_id)
				{
					$menuOrderObj = new Menu_Controller_Helper_MenuOrders($menu_id);
					$result = $menuOrderObj->saveMenuOrder($menu_order_arr[$i]);
					if($result['status'] == 'err')
					{
						$json_arr = array('status' => 'err','msg' => $result['msg']);
						break;
					}
					$i++;
				}
				$msg = $translator->translator("menu_zero_order_success");
				$json_arr = array('status' => 'ok','msg' => $msg);
			}			
			
			//Convert To JSON ARRAY	
			$res_value = Zend_Json::encode($json_arr);	
			$this->_response->setBody($res_value);
		}
	}
	
	//MENU GROUP FUNCTIONS
	
	public function addgrpAction()
	{		
		if ($this->_request->isPost()) 
		{
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			$this->_helper->viewRenderer->setNoRender();
			$this->_helper->layout->disableLayout();
			$translator = Zend_Registry::get('translator');
			
			if($this->menuGroupForm->isValid($this->_request->getPost())) 
			{	
				$menuGroup = new Menu_Model_Groups($this->menuGroupForm->getValues());
				$menuGroup->setActive('1');
								
				
				$perm = new Menu_View_Helper_Allow();
				if($perm->allow())
				{
					$last_id = $menuGroup->saveMenuGroup();
					if($last_id)
					{
						$groupTable = new Menu_Model_DbTable_MenuGroup();
						$group_row = $groupTable->getGroupName($last_id);
						$menu_group_helper = new Menu_View_Helper_MenuGroup();
						$num_of_menu = $menu_group_helper->getNumOfMenu($last_id);
						
						$msg = $translator->translator("menu_group_save_success");
						$json_arr = array('status' => 'ok','msg' => $msg, 'datas' => array('id' => $last_id, 'group_name' => $group_row['group_name'], 'active' => $group_row['active'], 'num_menu' => $num_of_menu));
					}
					else
					{
						$msg = $translator->translator("menu_group_save_err");
						$json_arr = array('status' => 'err','msg' => $msg);
					}	
				}
				else
				{
					$Msg =  $translator->translator("menu_group_add_action_deny_desc");
					$json_arr = array('status' => 'errP','msg' => $Msg);
				}
			
			}
			else
			{
				$validatorMsg = $this->menuGroupForm->getMessages();
				$vMsg = array();
				$i = 0;
				foreach($validatorMsg as $key => $errType)
				{					
					foreach($errType as $errkey => $value)
					{
						$vMsg[$i] = array('key' => $key, 'errKey' => $errkey, 'value' => $value);
						$i++;
					}
				}
				$json_arr = array('status' => 'errV','msg' => $vMsg);
			}			
			$res_value = Zend_Json::encode($json_arr);			
			$this->_response->setBody($res_value);
		}
		else
		{				
			$this->view->menuGroupForm = $this->menuGroupForm;			
			$this->render();
		}	
	}
	
	public function editgrpAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		$id = $this->_getParam('id', 0);
			
		if ($this->_request->isPost()) 
		{
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);			
			if($this->menuGroupForm->isValid($this->_request->getPost())) 
			{	
				$id = $this->_request->getPost('id');
				$menuGroup = new Menu_Model_Groups($this->menuGroupForm->getValues());
				$menuGroup->setId($id);								
				
				$perm = new Menu_View_Helper_Allow();
				if($perm->allow())
				{
					$last_id = $menuGroup->saveMenuGroup();
					if($last_id)
					{
						$groupTable = new Menu_Model_DbTable_MenuGroup();
						$group_row = $groupTable->getGroupName($id);
						$menu_group_helper = new Menu_View_Helper_MenuGroup();
						$num_of_menu = $menu_group_helper->getNumOfMenu($id);
						
						$msg = $translator->translator("menu_group_save_success");
						$json_arr = array('status' => 'ok','msg' => $msg, 'datas' => array('id' => $last_id, 'group_name' => $group_row['group_name'], 'active' => $group_row['active'], 'num_menu' => $num_of_menu));
					}
					else
					{
						$msg = $translator->translator("menu_group_save_err");
						$json_arr = array('status' => 'err','msg' => $msg);
					}	
				}
				else
				{
					$Msg =  $translator->translator("menu_group_add_action_deny_desc");
					$json_arr = array('status' => 'errP','msg' => $Msg);
				}
			
			}
			else
			{
				$validatorMsg = $this->menuGroupForm->getMessages();
				$vMsg = array();
				$i = 0;
				foreach($validatorMsg as $key => $errType)
				{					
					foreach($errType as $errkey => $value)
					{
						$vMsg[$i] = array('key' => $key, 'errKey' => $errkey, 'value' => $value);
						$i++;
					}
				}
				$json_arr = array('status' => 'errV','msg' => $vMsg);
			}			
			$res_value = Zend_Json::encode($json_arr);			
			$this->_response->setBody($res_value);
		}
		else
		{	
			$Group = new Menu_Model_DbTable_MenuGroup();
			$GroupData = $Group->getGroupName($id);
			$json_arr = array('status' => 'ok','msg' => $msg, 'datas' => $GroupData);
			$res_value = Zend_Json::encode($json_arr);			
			$this->_response->setBody($res_value);			
		}	
	}
	
	public function publishgrpAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{	
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			$id = $this->_request->getPost('id');
			$group_name = $this->_request->getPost('group_name');
			$paction = $this->_request->getPost('paction');
			
			switch($paction)
			{
				case 'publish':
					$active = '1';
					break;
				case 'unpublish':
					$active = '0';
					break;
			}
			
			//DB Connection
			$conn = Zend_Registry::get('msqli_connection');
			$conn->getConnection();
			
			// Update Article status
			$where = array();
			$where[] = 'id = '.$conn->quote($id);
					
			try
			{
				$conn->update(Zend_Registry::get('dbPrefix').'menu_group',array('active' => $active), $where);		
				$json_arr = array('status' => 'ok','active' => $active);
			}
			catch (Exception $e) 
			{
				$msg = $translator->translator('menu_list_publish_err',$group_name);	
				$json_arr = array('status' => 'err','msg' => $msg." ".$e->getMessage());
			}
		}
		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
			
	}
	
	public function publishallgrpAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{	
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			$id_str = $this->_request->getPost('id_st');
			$paction = $this->_request->getPost('paction');
			
			switch($paction)
			{
				case 'publish':
					$active = '1';
					break;
				case 'unpublish':
					$active = '0';
					break;
			}
			
			if(!empty($id_str))
			{
				$id_arr = explode(',',$id_str);
				
				//DB Connection
				$conn = Zend_Registry::get('msqli_connection');
				$conn->getConnection();
				
				foreach($id_arr as $id)
				{
					// Update Article status
					$where = array();
					$where[] = 'id = '.$conn->quote($id);
					try
					{
						$conn->update(Zend_Registry::get('dbPrefix').'menu_group',array('active' => $active), $where);
						$return = true;
					}
					catch (Exception $e) 
					{
						$return = false;
					}
				}
			
				if($return)
				{			
					$json_arr = array('status' => 'ok');
				}
				else
				{
					$msg = $translator->translator('menu_group_list_publish_err');	
					$json_arr = array('status' => 'err','msg' => $msg);
				}
			}
			else
			{
				$msg = $translator->translator("menu_group_selected_err");
				$json_arr = array('status' => 'err','msg' => $msg);
			}
		}		
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);			
	}
	
	public function deletegrpAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		
		if ($this->_request->isPost()) 
		{	
			$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			$id = $this->_request->getPost('id');
			$group_name = $this->_request->getPost('group_name');
						
			//DB Connection
			$conn = Zend_Registry::get('msqli_connection');
			$conn->getConnection();
			
			$check_num_menu = new Menu_View_Helper_MenuGroup();
					
			if($check_num_menu->getNumOfMenu($id) == '0')
			{
				// Remove from Menus
				$where = array();
				$where[] = 'id = '.$conn->quote($id);
				try
				{
					$conn->delete(Zend_Registry::get('dbPrefix').'menu_group', $where);
					$msg = 	$translator->translator('menu_group_delete_success',$group_name);			
					$json_arr = array('status' => 'ok','msg' => $msg);
				}
				catch (Exception $e) 
				{					
					$msg = 	$translator->translator('menu_list_delete_err');			
					$json_arr = array('status' => 'err','msg' => $msg);
				}
			}
			else
			{
				$msg = 	$translator->translator('menu_group_delete_err',$group_name);			
				$json_arr = array('status' => 'err','msg' => $msg);
			}
		}
		else
		{
			$msg = 	$translator->translator('menu_list_delete_err');			
			$json_arr = array('status' => 'err','msg' => $msg);
		}
		//Convert To JSON ARRAY	
		$res_value = Zend_Json::encode($json_arr);	
		$this->_response->setBody($res_value);
	}
	
	
	public function uploadfileAction()
	{
		$theme = Zend_Registry::get('jtheme');
		$this->view->theme = $theme;
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		$this->uploadForm = new Menu_Form_UploadForm();		
				
		$path = 'data/frontImages/menuImages';
		
		if ($this->_request->isPost()) 
		{
			$this->_helper->viewRenderer->setNoRender();
			
			if($this->uploadForm->isValid($this->_request->getPost())) 
			{				
				Members_Controller_Helper_FileRename::fileRename($this->uploadForm->upload_file, $path);
				$Filename = $this->uploadForm->upload_file->getFileName();
				$ext = Eicra_File_Utility::GetExtension($Filename);
				$option['file_type']	=	'jpg,png,gif';
				$file_obj = new Settings_Controller_Helper_File($path,$Filename,$ext,$option);
				if($file_obj->checkThumbExt())
				{				
					if($this->uploadForm->upload_file->receive())
					{
						$msg = $translator->translator('File_upload_success');
						$json_arr = array('status' => 'ok','msg' => $msg, 'newName' => $this->uploadForm->upload_file->getFileName(null,false));
					}
					else
					{
						$validatorMsg = $this->uploadForm->upload_file->getMessages();
						$vMsg = implode("\n", $validatorMsg);	
						$json_arr = array('status' => 'err','msg' => $vMsg, 'newName' => $this->uploadForm->upload_file->getFileName(null,false));	
					}
				}
				else
				{
					$msg = $translator->translator('File_upload_ext_err',$ext, 'Settings');
					$json_arr = array('status' => 'err','msg' => $msg, 'newName' => $this->uploadForm->upload_file->getFileName(null,false));
				}
			}
			else
			{
				$validatorMsg = $this->uploadForm->getMessages();
				$vMsg = array();
				$i = 0;
				foreach($validatorMsg as $key => $errType)
				{					
					foreach($errType as $errkey => $value)
					{
						$vMsg[$i] = array('key' => $key, 'errKey' => $errkey, 'value' => $value);
						$i++;
					}
				}
				$json_arr = array('status' => 'err','msg' => $vMsg);
			}
			//Convert To JSON ARRAY	
			$res_value = Zend_Json::encode($json_arr);	
			$this->_response->setBody($res_value);						
		}	
		else
		{
			$this->view->upload_path	=	$path;
			$this->view->uploadForm = $this->uploadForm;			
			$this->render();
		}		
	}
	
	public function deletefileAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$translator = Zend_Registry::get('translator');
		$this->view->translator	= $translator;
		
		if ($this->_request->isPost()) 
		{
			$file_info = $this->_request->getPost('file_info');
			if(empty($file_info))
			{
				$msg = $translator->translator("insert_selected_file_err");
				$json_arr = array('status' => 'err','msg' => $msg);
			}
			else
			{
				$file_info_arr = explode(',',$file_info);
				$file_obj = new Eicra_File_Utility();
				
				if($file_obj->deleteRescursiveDir( $file_info_arr[0]."/".$file_info_arr[1]))
				{
					$msg = $translator->translator("file_delete_success",$file_info_arr[1]);
					$json_arr = array('status' => 'ok','msg' => $msg);
				}
				else
				{
					$msg = $translator->translator("file_delete_err",$file_info_arr[1]);
					$json_arr = array('status' => 'err','msg' => $msg);
				}
			}
		}
		else
		{
			$msg = $translator->translator("file_delete_err");
			$json_arr = array('status' => 'err','msg' => $msg);
		}		
		$res_value = Zend_Json::encode($json_arr);			
		$this->_response->setBody($res_value);
	}		
}