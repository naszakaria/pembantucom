<?php

class Property_EmailsController extends Zend_Controller_Action
{
	private $_DBconn;
	private $_page_id;
	private $_controllerCache;
	
    public function init()
    {
        /* Initialize action controller here */				
		$translator = Zend_Registry::get('translator');
		$this->view->assign('translator', $translator);	
		$this->view->setEscape('stripslashes');	
			
		//DB Connection
		$this->_DBconn = Zend_Registry::get('msqli_connection');
		$this->_DBconn->getConnection();
		
		//Initialize Cache
		$cache = new Eicra_View_Helper_Cache();
		$this->_controllerCache = 	$cache->getCache();					
    }
	
	public function preDispatch() 
	{			
		$template_obj = new Eicra_View_Helper_Template;	
		$template_obj->setFrontendTemplate();			
		$front_template = Zend_Registry::get('front_template');
		$this->_helper->layout->setLayoutPath(APPLICATION_PATH.'/layouts/scripts/'.$front_template['theme_folder']);
		$this->_helper->layout->setLayout($template_obj->getLayout());		
		
		if($this->_request->getParam('menu_id'))
		{				
			$viewHelper = new Eicra_VHelper_ViewHelper($this->_request);
			$page_id_arr = $viewHelper->_getContentId();
			
			$this->_page_id = (!empty($page_id_arr[0])) ? $page_id_arr[0] : null ;
		}
		else
		{
			$this->_page_id = null;
		}
	}
	public function newfriendAction()
    {	
		if($this->_request->getParam('property_title'))
		{
			$property_title = $this->_request->getParam('property_title');
			$property_db = new Property_Model_DbTable_Properties();
			$property_info = $property_db->getTitleToId($property_title);			
			$this->view->property_info	=	$property_info[0];
		}
		$form_id = ($this->_page_id) ? $this->_page_id	: $this->_request->getParam('form_id');	
		if($form_id)
		{								
			$form_db = new Members_Model_DbTable_Forms();
			$form_info = $form_db->getFormsInfo($form_id);	
			$generalForm =  new Members_Form_GeneralForm ($form_info);
			
			if($form_info['login_set'] == '1')
			{
				$getAction = $this->_request->getActionName();
				if($getAction != 'uploadfile')
				{			
					$url = $this->view->url(array('module'=> 'Members', 'controller' => 'frontend', 'action'     => 'login' ), 'Frontend-Login',    true);
					
					Eicra_Global_Variable::checkSession($this->_response,$url);
				}
			}
			
			if ($this->_request->isPost()) 
			{	
				$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
				$this->_helper->viewRenderer->setNoRender();
				$this->_helper->layout->disableLayout();
				$translator = Zend_Registry::get('translator');				
				
				$elements = $generalForm->getElements();
				foreach($elements as $element)
				{
					if($element->getType() == 'Zend_Form_Element_File')
					{
						$element_name = $element->getName();
						$generalForm->removeElement($element_name);
					}
				}
				if ($generalForm->isValid($this->_request->getPost())) 
				{
					$generalForm =  new Members_Form_GeneralForm ($form_info);
					$generalForm->populate($this->_request->getPost());
					$fromValues = $generalForm;	
					if($form_info['db_set'] == '1')
					{
						$general = new Members_Model_General(array('form_id' => $form_info['id'],'active' => '2'));
						$result = $general->saveGeneral();
					}
					else
					{
						$result['status'] = 'ok';
						$result['id'] = 0;
					}
					
					if($result['status'] == 'ok')
					{				
						$field_db = new Members_Model_DbTable_Fields();
						$field_groups = $field_db->getGroupNames($form_info['id']); 
						
						//Add Data To Database
						$atta_count = 0;
						$attach_file_arr = array();
						foreach($field_groups as $group)
						{
							$group_name = $group->field_group;
							$displaGroup = $generalForm->getDisplayGroup($group_name);
							$elementsObj = $displaGroup->getElements();
							foreach($elementsObj as $element)
							{
								$table_id = $result['id'];
								$form_id = $form_info['id'];
								$field_id	=	$element->getAttrib('rel');
								$field_value	=	($element->getType() == 'Zend_Form_Element_File') ? $this->_request->getPost($element->getName()) : $element->getValue();
								if($element->getType() == 'Zend_Form_Element_File') { $attach_file_arr[$atta_count] = $this->_request->getPost($element->getName()); $atta_count++; }
								try
								{
									$msg = $translator->translator("property_form_submit_successfull");
									$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
									
									if($form_info['db_set'] == '1')
									{
										$data = array('table_id' => $table_id, 'form_id' => $form_id, 'field_id' => $field_id, 'field_value' => $field_value);
										$this->_DBconn = Zend_Registry::get('msqli_connection');
										$this->_DBconn->getConnection();
										$this->_DBconn->insert(Zend_Registry::get('dbPrefix').'forms_fields_values',$data);
										
										$msg = $translator->translator("property_form_submit_successfull");
										$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
									}								
									
								}
								catch(Exception $e)
								{
									$msg = $translator->translator("member_form_submit_err");
									$json_arr = array('status' => 'err','msg' =>  $msg.' '.$e->getMessage(), 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
							}						
						}
						
						//Send Data To Email
						if($form_info['email_set'] == '1')
						{
							$register_helper = new Members_Controller_Helper_Registers();	
							$allDatas = $fromValues->getValues();	
							try 
							{
								$allDatas['property_url'] =	($property_info) ? $this->view->serverUrl().$this->view->baseUrl().'/Property-details/'.$this->view->escape($property_info[0]['property_title']) : '#';
								$allDatas['property_name'] = $this->view->escape($property_info[0]['property_name']);
								$from_mail_field = Eicra_File_Constants::FROM_MAIL;
								$to_mail_field = Eicra_File_Constants::TO_MAIL;
								$allDatas[$from_mail_field] = trim($this->_request->getPost($from_mail_field));
								$to_mail	=	trim($this->_request->getPost($to_mail_field));
								$send_result = $register_helper->sendEmailToFriend($allDatas,$form_info,$attach_file_arr,$to_mail);
								if($send_result['status'] == 'ok')
								{
									$msg = $translator->translator("property_form_submit_successfull");
									$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
								else
								{
									$json_arr = array('status' => 'err','msg' => $send_result['msg'], 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
							}
							catch (Exception $e) 
							{
								$msg = $e->getMessage();
								$json_arr = array('status' => 'err','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
							}
						}
						
						//Delete Attached Files
						if($form_info['attach_file_delete'] == '1')
						{
							$elements = $generalForm->getElements();
							foreach($elements as $element)
							{
								if($element->getType() == 'Zend_Form_Element_File')
								{
									$element_name = $element->getName();
									$element_value	= $this->_request->getPost($element_name);
									if(!empty($element_value))
									{
										$element_value_arr = explode(',',$element_value);	
										foreach($element_value_arr as $key => $e_value)
										{
											if(!empty($e_value))
											{
												$dir = BASE_PATH.DS.$form_info['attach_file_path'].DS.$e_value;
												$res = Eicra_File_Utility::deleteRescursiveDir($dir);	
											}
										}
									}
								}
							}
						}
					}
					else
					{
						$json_arr = array('status' => 'err','msg' => $result['msg'], 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
					}
												
				}
				else
				{
					$validatorMsg = $generalForm->getMessages();
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
					$json_arr = array('status' => 'errV','msg' => $vMsg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
				}			
				$res_value = Zend_Json_Encoder::encode($json_arr);			
				$this->_response->setBody($res_value);
			}
			
			$this->view->form_info	= $form_info;
			$this->view->generalForm = $generalForm;
			$this->dynamicUploaderSettings($this->view->form_info);	
		}
	}
	
	public function friendAction()
    {	
		if($this->_request->getParam('property_title'))
		{
			$property_title = $this->_request->getParam('property_title');
			$property_db = new Property_Model_DbTable_Properties();
			$property_info = $property_db->getTitleToId($property_title);			
			$this->view->property_info	=	$property_info[0];
		}
		$form_id = ($this->_page_id) ? $this->_page_id	: $this->_request->getParam('form_id');	
		if($form_id)
		{								
			$form_db = new Members_Model_DbTable_Forms();
			$form_info = $form_db->getFormsInfo($form_id);	
			$generalForm =  new Members_Form_GeneralForm ($form_info);
			
			if($form_info['login_set'] == '1')
			{
				$getAction = $this->_request->getActionName();
				if($getAction != 'uploadfile')
				{			
					$url = $this->view->url(array('module'=> 'Members', 'controller' => 'frontend', 'action'     => 'login' ), 'Frontend-Login',    true);
					
					Eicra_Global_Variable::checkSession($this->_response,$url);
				}
			}
			
			if ($this->_request->isPost()) 
			{	
				$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
				$this->_helper->viewRenderer->setNoRender();
				$this->_helper->layout->disableLayout();
				$translator = Zend_Registry::get('translator');				
				
				$elements = $generalForm->getElements();
				foreach($elements as $element)
				{
					if($element->getType() == 'Zend_Form_Element_File')
					{
						$element_name = $element->getName();
						$generalForm->removeElement($element_name);
					}
				}
				if ($generalForm->isValid($this->_request->getPost())) 
				{
					$generalForm =  new Members_Form_GeneralForm ($form_info);
					$generalForm->populate($this->_request->getPost());
					$fromValues = $generalForm;	
					if($form_info['db_set'] == '1')
					{
						$general = new Members_Model_General(array('form_id' => $form_info['id'],'active' => '2'));
						$result = $general->saveGeneral();
					}
					else
					{
						$result['status'] = 'ok';
						$result['id'] = 0;
					}
					
					if($result['status'] == 'ok')
					{				
						$field_db = new Members_Model_DbTable_Fields();
						$field_groups = $field_db->getGroupNames($form_info['id']); 
						
						//Add Data To Database
						$atta_count = 0;
						$attach_file_arr = array();
						foreach($field_groups as $group)
						{
							$group_name = $group->field_group;
							$displaGroup = $generalForm->getDisplayGroup($group_name);
							$elementsObj = $displaGroup->getElements();
							foreach($elementsObj as $element)
							{
								$table_id = $result['id'];
								$form_id = $form_info['id'];
								$field_id	=	$element->getAttrib('rel');
								$field_value	=	($element->getType() == 'Zend_Form_Element_File') ? $this->_request->getPost($element->getName()) : $element->getValue();
								if($element->getType() == 'Zend_Form_Element_File') { $attach_file_arr[$atta_count] = $this->_request->getPost($element->getName()); $atta_count++; }
								try
								{
									$msg = $translator->translator("property_form_submit_successfull");
									$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
									
									if($form_info['db_set'] == '1')
									{
										$data = array('table_id' => $table_id, 'form_id' => $form_id, 'field_id' => $field_id, 'field_value' => $field_value);
										$this->_DBconn = Zend_Registry::get('msqli_connection');
										$this->_DBconn->getConnection();
										$this->_DBconn->insert(Zend_Registry::get('dbPrefix').'forms_fields_values',$data);
										
										$msg = $translator->translator("property_form_submit_successfull");
										$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
									}								
									
								}
								catch(Exception $e)
								{
									$msg = $translator->translator("member_form_submit_err");
									$json_arr = array('status' => 'err','msg' =>  $msg.' '.$e->getMessage(), 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
							}						
						}
						
						//Send Data To Email
						if($form_info['email_set'] == '1')
						{
							$register_helper = new Members_Controller_Helper_Registers();	
							$allDatas = $fromValues->getValues();	
							try 
							{
								$allDatas['property_url'] =	($property_info) ? $this->view->serverUrl().$this->view->baseUrl().'/Property-details/'.$this->view->escape($property_info[0]['property_title']) : '#';
								$allDatas['property_name'] = $this->view->escape($property_info[0]['property_name']);
								$from_mail_field = Eicra_File_Constants::FROM_MAIL;
								$to_mail_field = Eicra_File_Constants::TO_MAIL;
								$allDatas[$from_mail_field] = trim($this->_request->getPost($from_mail_field));
								$to_mail	=	trim($this->_request->getPost($to_mail_field));
								$send_result = $register_helper->sendEmailToFriend($allDatas,$form_info,$attach_file_arr,$to_mail);
								if($send_result['status'] == 'ok')
								{
									$msg = $translator->translator("property_form_submit_successfull");
									$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
								else
								{
									$json_arr = array('status' => 'err','msg' => $send_result['msg'], 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
							}
							catch (Exception $e) 
							{
								$msg = $e->getMessage();
								$json_arr = array('status' => 'err','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
							}
						}
						
						//Delete Attached Files
						if($form_info['attach_file_delete'] == '1')
						{
							$elements = $generalForm->getElements();
							foreach($elements as $element)
							{
								if($element->getType() == 'Zend_Form_Element_File')
								{
									$element_name = $element->getName();
									$element_value	= $this->_request->getPost($element_name);
									if(!empty($element_value))
									{
										$element_value_arr = explode(',',$element_value);	
										foreach($element_value_arr as $key => $e_value)
										{
											if(!empty($e_value))
											{
												$dir = BASE_PATH.DS.$form_info['attach_file_path'].DS.$e_value;
												$res = Eicra_File_Utility::deleteRescursiveDir($dir);	
											}
										}
									}
								}
							}
						}
					}
					else
					{
						$json_arr = array('status' => 'err','msg' => $result['msg'], 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
					}
												
				}
				else
				{
					$validatorMsg = $generalForm->getMessages();
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
					$json_arr = array('status' => 'errV','msg' => $vMsg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
				}			
				$res_value = Zend_Json_Encoder::encode($json_arr);			
				$this->_response->setBody($res_value);
			}
			
			$this->view->form_info	= $form_info;
			$this->view->generalForm = $generalForm;
			$this->dynamicUploaderSettings($this->view->form_info);	
		}
	}
	
	public function ownerAction()
    {	
		if($this->_request->getParam('property_title'))
		{
			$property_title = $this->_request->getParam('property_title');
			$property_db = new Property_Model_DbTable_Properties();
			$property_info = $property_db->getTitleToId($property_title);			
			$this->view->property_info	=	$property_info[0];
		}
		$form_id = ($this->_page_id) ? $this->_page_id	: $this->_request->getParam('form_id');	
		if($form_id)
		{								
			$form_db = new Members_Model_DbTable_Forms();
			$form_info = $form_db->getFormsInfo($form_id);	
			$generalForm =  new Members_Form_GeneralForm ($form_info);
			
			if($form_info['login_set'] == '1')
			{
				$getAction = $this->_request->getActionName();
				if($getAction != 'uploadfile')
				{			
					$url = $this->view->url(array('module'=> 'Members', 'controller' => 'frontend', 'action'     => 'login' ), 'Frontend-Login',    true);
					
					Eicra_Global_Variable::checkSession($this->_response,$url);
				}
			}
			
			if ($this->_request->isPost()) 
			{	
				$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
				$this->_helper->viewRenderer->setNoRender();
				$this->_helper->layout->disableLayout();
				$translator = Zend_Registry::get('translator');				
				
				$elements = $generalForm->getElements();
				foreach($elements as $element)
				{
					if($element->getType() == 'Zend_Form_Element_File')
					{
						$element_name = $element->getName();
						$generalForm->removeElement($element_name);
					}
				}
				if ($generalForm->isValid($this->_request->getPost())) 
				{
					$generalForm =  new Members_Form_GeneralForm ($form_info);
					$generalForm->populate($this->_request->getPost());
					$fromValues = $generalForm;	
					
					if($form_info['db_set'] == '1')
					{
						$property_owner_user_id = (!empty($property_info[0]['property_owner']) && is_numeric($property_info[0]['property_owner'])) ? $property_info[0]['property_owner'] : null;
						$general = new Members_Model_General(array('form_id' => $form_info['id'],'active' => '2', 'user_id' => $property_owner_user_id));						
						$result = $general->saveGeneral();
					}
					else
					{
						$result['status'] = 'ok';
						$result['id'] = 0;
					}
					
					if($result['status'] == 'ok')
					{				
						$field_db = new Members_Model_DbTable_Fields();
						$field_groups = $field_db->getGroupNames($form_info['id']); 
						
						//Add Data To Database
						$atta_count = 0;
						$attach_file_arr = array();
						foreach($field_groups as $group)
						{
							$group_name = $group->field_group;
							$displaGroup = $generalForm->getDisplayGroup($group_name);
							$elementsObj = $displaGroup->getElements();
							foreach($elementsObj as $element)
							{
								$table_id = $result['id'];
								$form_id = $form_info['id'];
								$field_id	=	$element->getAttrib('rel');
								$field_value	=	($element->getType() == 'Zend_Form_Element_File') ? $this->_request->getPost($element->getName()) : $element->getValue();
								if($element->getType() == 'Zend_Form_Element_File') { $attach_file_arr[$atta_count] = $this->_request->getPost($element->getName()); $atta_count++; }
								try
								{
									$msg = $translator->translator("property_form_submit_successfull");
									$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
									
									if($form_info['db_set'] == '1')
									{
										$data = array('table_id' => $table_id, 'form_id' => $form_id, 'field_id' => $field_id, 'field_value' => $field_value);
										$this->_DBconn = Zend_Registry::get('msqli_connection');
										$this->_DBconn->getConnection();
										$this->_DBconn->insert(Zend_Registry::get('dbPrefix').'forms_fields_values',$data);
										
										$msg = $translator->translator("property_form_submit_successfull");
										$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
									}								
									
								}
								catch(Exception $e)
								{
									$msg = $translator->translator("member_form_submit_err");
									$json_arr = array('status' => 'err','msg' =>  $msg.' '.$e->getMessage(), 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
							}						
						}
						
						//Send Data To Email
						if($form_info['email_set'] == '1')
						{
							$register_helper = new Members_Controller_Helper_Registers();	
							$allDatas = $fromValues->getValues();	
							try 
							{
								$allDatas[Eicra_File_Constants::PROPERTY_URL] =	($property_info) ? $this->view->serverUrl().$this->view->baseUrl().'/Property-details/'.$this->view->escape($property_info[0]['property_title']) : '#';
								$allDatas[Eicra_File_Constants::PROPERTY_NAME] = $this->view->escape($property_info[0]['property_name']);
								$mem_db = new Members_Model_DbTable_MemberList();
								$global_conf = Zend_Registry::get('global_conf');
								$to_mail	=	$global_conf['global_email'];
								$to_mail_field = Eicra_File_Constants::TO_MAIL;
								$secondary_email	=	trim($this->_request->getPost($to_mail_field));	
								
								
								if(!$this->isnot_int($property_info[0]['property_owner']))
								{
									$mem_info = $mem_db->getMemberInfo($property_info[0]['property_owner']);
									//Package Info 
									$packages = new Members_Controller_Helper_Packages();
									if($packages->isPackageFrontend($mem_info['user_id'],$mem_info['role_id']))
									{								
										if($packages->isPackageFrontendField('Property','frontend','details',$mem_info['user_id'],$mem_info['role_id']))
										{
											$package_no = $packages->getPackageFrontendFieldValue('Property','frontend','details',$mem_info['user_id'],$mem_info['role_id']);				
										}
										else
										{
											$package_no = 'Off';
										}
									}
									else
									{
										$package_no = 'Off';
									}
									if($package_no == 'On')
									{
										if($packages->isPackageFrontendField('Property','frontend','details'.'_contact_directly',$mem_info['user_id'],$mem_info['role_id']))
										{
											$contact_directly = $packages->getPackageFrontendFieldValue('Property','frontend','details'.'_contact_directly',$mem_info['user_id'],$mem_info['role_id']);				
										}
										else
										{
											$contact_directly = 'No';
										}
									}
									else
									{
										$contact_directly = 'Yes';
									}									
									//$secondary_email	=	($contact_directly == 'Yes') ? $mem_info['username'] : '';
								}
								
								$send_result = $register_helper->sendEmailToFriend($allDatas,$form_info,$attach_file_arr,$to_mail, $secondary_email);
								if($send_result['status'] == 'ok')
								{
									$msg = $translator->translator("property_form_submit_successfull");
									$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
								else
								{
									$json_arr = array('status' => 'err','msg' => $send_result['msg'], 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
							}
							catch (Exception $e) 
							{
								$msg = $e->getMessage();
								$json_arr = array('status' => 'err','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
							}
						}
						
						//Delete Attached Files
						if($form_info['attach_file_delete'] == '1')
						{
							$elements = $generalForm->getElements();
							foreach($elements as $element)
							{
								if($element->getType() == 'Zend_Form_Element_File')
								{
									$element_name = $element->getName();
									$element_value	= $this->_request->getPost($element_name);
									if(!empty($element_value))
									{	
										$element_value_arr = explode(',',$element_value);	
										foreach($element_value_arr as $key => $e_value)
										{
											if(!empty($e_value))
											{
												$dir = BASE_PATH.DS.$form_info['attach_file_path'].DS.$e_value;
												$res = Eicra_File_Utility::deleteRescursiveDir($dir);	
											}
										}
									}
								}
							}
						}
					}
					else
					{
						$json_arr = array('status' => 'err','msg' => $result['msg'], 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
					}
												
				}
				else
				{
					$validatorMsg = $generalForm->getMessages();
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
					$json_arr = array('status' => 'errV','msg' => $vMsg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
				}			
				$res_value = Zend_Json_Encoder::encode($json_arr);			
				$this->_response->setBody($res_value);
			}
			
			$this->view->form_info	= $form_info;
			$this->view->generalForm = $generalForm;
			$this->dynamicUploaderSettings($this->view->form_info);	
		}
	}
	
	public function appointmentAction()
    {	
		if($this->_request->getParam('property_title'))
		{
			$property_title = $this->_request->getParam('property_title');
			$property_db = new Property_Model_DbTable_Properties();
			$property_info = $property_db->getTitleToId($property_title);			
			$this->view->property_info	=	$property_info[0];
		}
		$form_id = ($this->_page_id) ? $this->_page_id	: $this->_request->getParam('form_id');	
		if($form_id)
		{								
			$form_db = new Members_Model_DbTable_Forms();
			$form_info = $form_db->getFormsInfo($form_id);	
			$generalForm =  new Members_Form_GeneralForm ($form_info);
			
			if($form_info['login_set'] == '1')
			{
				$getAction = $this->_request->getActionName();
				if($getAction != 'uploadfile')
				{			
					$url = $this->view->url(array('module'=> 'Members', 'controller' => 'frontend', 'action'     => 'login' ), 'Frontend-Login',    true);
					Eicra_Global_Variable::checkSession($this->_response,$url);
				}
			}
			
			if ($this->_request->isPost()) 
			{	
				$this->_controllerCache->clean(Zend_Cache::CLEANING_MODE_ALL);
				$this->_helper->viewRenderer->setNoRender();
				$this->_helper->layout->disableLayout();
				$translator = Zend_Registry::get('translator');				
				
				$elements = $generalForm->getElements();
				foreach($elements as $element)
				{
					if($element->getType() == 'Zend_Form_Element_File')
					{
						$element_name = $element->getName();
						$generalForm->removeElement($element_name);
					}
				}
				
				if ($generalForm->isValid($this->_request->getPost())) 
				{
					$generalForm =  new Members_Form_GeneralForm ($form_info);
					$generalForm->populate($this->_request->getPost());
					$fromValues = $generalForm;	
					if($form_info['db_set'] == '1')
					{
						$general = new Members_Model_General(array('form_id' => $form_info['id'],'active' => '0'));
						$result = $general->saveGeneral();
					}
					else
					{
						$result['status'] = 'ok';
						$result['id'] = 0;
					}
					
					if($result['status'] == 'ok')
					{				
						$field_db = new Members_Model_DbTable_Fields();
						$field_groups = $field_db->getGroupNames($form_info['id']); 
						
						//Add Data To Database
						$atta_count = 0;
						$attach_file_arr = array();
						foreach($field_groups as $group)
						{
							$group_name = $group->field_group;
							$displaGroup = $generalForm->getDisplayGroup($group_name);
							$elementsObj = $displaGroup->getElements();
							foreach($elementsObj as $element)
							{
								$table_id = $result['id'];
								$form_id = $form_info['id'];
								$field_id	=	$element->getAttrib('rel');
								$field_value	=	($element->getType() == 'Zend_Form_Element_File') ? $this->_request->getPost($element->getName()) : $element->getValue();
								if($element->getType() == 'Zend_Form_Element_File') { $attach_file_arr[$atta_count] = $this->_request->getPost($element->getName()); $atta_count++; }
								try
								{
									$msg = $translator->translator("property_form_submit_successfull");
									$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
									
									if($form_info['db_set'] == '1')
									{
										$data = array('table_id' => $table_id, 'form_id' => $form_id, 'field_id' => $field_id, 'field_value' => $field_value);
										$this->_DBconn = Zend_Registry::get('msqli_connection');
										$this->_DBconn->getConnection();
										$this->_DBconn->insert(Zend_Registry::get('dbPrefix').'forms_fields_values',$data);
										
										$msg = $translator->translator("property_form_submit_successfull");
										$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
									}								
									
								}
								catch(Exception $e)
								{
									$msg = $translator->translator("member_form_submit_err");
									$json_arr = array('status' => 'err','msg' =>  $msg.' '.$e->getMessage(), 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
							}						
						}
						
						//Send Data To Email
						if($form_info['email_set'] == '1')
						{
							$register_helper = new Members_Controller_Helper_Registers();							
							$allDatas = $fromValues->getValues();	
							try 
							{
															
								$allDatas[Eicra_File_Constants::PROPERTY_URL] =	($property_info) ? $this->view->serverUrl().$this->view->baseUrl().'/Property-details/'.$this->view->escape($property_info[0]['property_title']) : '#';
								$allDatas[Eicra_File_Constants::PROPERTY_NAME] = $this->view->escape($property_info[0]['property_name']);
								$mem_db = new Members_Model_DbTable_MemberList();
								$global_conf = Zend_Registry::get('global_conf');
								$to_mail	=	$global_conf['global_email'];	
								
								if(!$this->isnot_int($property_info[0]['property_owner']))
								{
									$mem_info = $mem_db->getMemberInfo($property_info[0]['property_owner']);
									
									//Package Info 
									$packages = new Members_Controller_Helper_Packages();
									if($packages->isPackageFrontend($mem_info['user_id'],$mem_info['role_id']))
									{								
										if($packages->isPackageFrontendField('Property','frontend','details',$mem_info['user_id'],$mem_info['role_id']))
										{
											$package_no = $packages->getPackageFrontendFieldValue('Property','frontend','details',$mem_info['user_id'],$mem_info['role_id']);				
										}
										else
										{
											$package_no = 'Off';
										}
									}
									else
									{
										$package_no = 'On';
									}
									if($package_no == 'On')
									{
										if($packages->isPackageFrontendField('Property','frontend','details'.'_contact_directly',$mem_info['user_id'],$mem_info['role_id']))
										{
											$contact_directly = $packages->getPackageFrontendFieldValue('Property','frontend','details'.'_contact_directly',$mem_info['user_id'],$mem_info['role_id']);				
										}
										else
										{
											$contact_directly = 'No';
										}
									}
									else
									{
										$contact_directly = 'Yes';
									}									
									$secondary_email	=	($contact_directly == 'Yes') ? $mem_info['username'] : '';
								}
								
								$send_result = $register_helper->sendEmailToFriend($allDatas,$form_info,$attach_file_arr,$to_mail,$secondary_email);
								if($send_result['status'] == 'ok')
								{
									$msg = $translator->translator("property_form_submit_successfull");
									$json_arr = array('status' => 'ok','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
								else
								{
									$json_arr = array('status' => 'err','msg' => $send_result['msg'], 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
								}
							}
							catch (Exception $e) 
							{
								$msg = $e->getMessage();
								$json_arr = array('status' => 'err','msg' => $msg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
							}
						}
						
						//Delete Attached Files
						if($form_info['attach_file_delete'] == '1')
						{
							$elements = $generalForm->getElements();
							foreach($elements as $element)
							{
								if($element->getType() == 'Zend_Form_Element_File')
								{
									$element_name = $element->getName();
									$element_value	= $this->_request->getPost($element_name);
									if(!empty($element_value))
									{	
										$element_value_arr = explode(',',$element_value);	
										foreach($element_value_arr as $key => $e_value)
										{
											if(!empty($e_value))
											{
												$dir = BASE_PATH.DS.$form_info['attach_file_path'].DS.$e_value;
												$res = Eicra_File_Utility::deleteRescursiveDir($dir);	
											}
										}
									}
								}
							}
						}
					}
					else
					{
						$json_arr = array('status' => 'err','msg' => $result['msg'], 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
					}
												
				}
				else
				{
					$validatorMsg = $generalForm->getMessages();
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
					$json_arr = array('status' => 'errV','msg' => $vMsg, 'captcha' => Members_Controller_Helper_Captcha::getCaptchaElement($generalForm));
				}			
				$res_value = Zend_Json_Encoder::encode($json_arr);			
				$this->_response->setBody($res_value);
			}
			
			$this->view->form_info	= $form_info;
			$this->view->generalForm = $generalForm;
			$this->dynamicUploaderSettings($this->view->form_info);	
		}
	}
	
	public function isnot_int($property_owner)
	{
		$r = true;			
		if(is_numeric($property_owner))
		{ 
			if(is_int((int)$property_owner))
			{
				$r = false;
			}				
		}
		return $r;
	}
	
	private function dynamicUploaderSettings($info)
	{
		$param_fields = array(
								'table_name' => 'forms', 
								'primary_id_field'	=>	'id', 
								'primary_id_field_value'	=>	$info['id'],
								'file_path_field'	=>	'attach_file_path', 
								'file_extension_field'	=>	'attach_file_type', 
								'file_max_size_field'	=>	'attach_file_max_size'
						);		
			$portfolio_model = new Portfolio_Model_Portfolio($param_fields);
			$requested_data = $portfolio_model->getRequestedData();
			$settings_info = $portfolio_model->getSettingInfo();
			$merge_data = array_merge($requested_data, $settings_info);	
			$this->view->assign('settings_info' ,	$merge_data);		
			$settings_json_info = Zend_Json_Encoder::encode($merge_data);	
			$this->view->assign('settings_json_info' ,	$settings_json_info);	
	}	
}