<?php
/**
* This is the DbTable class for the user_profile table.
*/
class Members_Model_DbTable_MemberList extends Eicra_Abstract_DbTable
{
    /** Table name */
    protected $_name    =  'user_profile';
	protected $_cols	=	null;
	
	public function getTableColumns() 
    {
		$metadata = $this->getAdapter()->describeTable($this->_name);
		$columnNames = array_keys($metadata);
		return $columnNames;
	}
		
	//Get Datas
	public function getMemberInfo($user_id) 
    {
        $user_id = (int)$user_id;      
		$select = $this->select()
						->setIntegrityCheck(false)
                       	->from(array('up' => $this->_name), array('up.*', 'full_name' => "CONCAT(up.title, ' ', up.firstName, ' ', up.lastName)"))
                       	->where('up.user_id = ?', $user_id)
					   	->joinLeft(array('cnt' => Zend_Registry::get('dbPrefix').'countries'), 'up.country = cnt.id', array('country_id' => 'cnt.id', 'country_name' => 'cnt.value'))
				   		->limit(1);	
					   
        $row = $this->getAdapter()->fetchAll($select);
        if ($row && is_array($row[0])) 
		{
           // throw new Exception("Count not find row $user_id");
		   $options = $row[0];
			$options = is_array($options) ? array_map('stripslashes', $options) : stripslashes($options);
        }
		else
		{
			$options = null;
		}
		
        return  $options;   
    }
	
	public function getMemberInfoByUsername($username) 
    {        
        $select = $this->select()
                       ->from($this->_name, array('*'))
                       ->where('username = ?', $username);
        $options = $this->getAdapter()->fetchAll($select);
        if ($options) 
		{
           // throw new Exception("Count not find row $username");
		   $options = $options[0];
		   $options = is_array($options) ? array_map('stripslashes', $options) : stripslashes($options);
        }
		else
		{
			$options = null;
		}
		
        return  $options;   
    }
	
	public function memberValidator($user_id = null) 
    {
		if(empty($user_id))
		{
			$validator = new Zend_Validate_Db_RecordExists(
					array(
						'table' => $this->_name,
						'field' => 'username'
					)
				);
		} 
		else
		{
			$validator = new Zend_Validate_Db_RecordExists(
					array(
						'table' => $this->_name,
						'field' => 'username',
						'exclude' => array('field' => 'user_id', 'value' => $user_id)
					)
				);
		}
		return $validator;
	}
	
	public function isAdministrator($username) 
    {
		$validator = new Zend_Validate_Db_RecordExists(
					array(
						'table' => $this->_name,
						'field' => 'username',
						'exclude' => 'role_id <= 101'
					)
				);
		return $validator->isValid($username);
	}
	
	public function getInactiveMembers()
    {
        $select = $this->select()
                       ->from($this->_name, array('COUNT(*) as num'))
                       ->where('status = ?','0');
        $options =  $this->fetchAll($select);
        return $options;
    }	
	
	public function getAllMembers()
    {
        $select = $this->select()
                       ->from($this->_name, array('user_id', 'name' => 'CONCAT(firstName," ",lastName)'))
                       ->order('firstName ASC');
        $options = $this->getAdapter()->fetchPairs($select);
		
        return $options;
    }	
	
	public function getMembers()
    {
        $select = $this->select()
                       ->from($this->_name, array('*'));
        $options = $this->fetchAll($select);
        return $options;
    }
	
	public function getMemberList($approve = null, $search_params = null)
    {					
		$auth = Zend_Auth::getInstance ();	
		$role_id = ($auth->hasIdentity ()) ? $auth->getIdentity()->role_id : '' ;
		$user_id = ($auth->hasIdentity ()) ? $auth->getIdentity()->user_id : '' ;
		
		if($approve == null)
		{
			$select = $this->select()
						   ->setIntegrityCheck(false)
						   ->from(array('m' => $this->_name),array('m.*', 'full_name' => "CONCAT( m.firstName, ' ', m.lastName)"))
						   ->joinLeft(array('r' => Zend_Registry::get('dbPrefix').'roles'), 'm.role_id = r.role_id')
						   ->where('r.role_lock = 1 OR r.role_id = '.$role_id);
							  // ->orwhere('r.role_id = ? ', $role_id); 
		}
		else
		{
			 $select = $this->select()
						   ->setIntegrityCheck(false)
						   ->from(array('m' => $this->_name),array('m.*', 'full_name' => "CONCAT( m.firstName, ' ', m.lastName)"))							   							   
						   ->joinLeft(array('r' => Zend_Registry::get('dbPrefix').'roles'), 'm.role_id = r.role_id')
						   ->where('r.role_lock = 1 OR r.role_id = '.$role_id)	
						   ->where('m.status = ? ', $approve); 
							 
		}
		
		if($search_params != null)
		{
			if($search_params['sort'])
			{				
				foreach($search_params['sort'] as $sort_key => $sort_value_arr)
				{
					$select->order($sort_value_arr['field'].' '.$sort_value_arr['dir']);					
				}
			}
			
			if($search_params['filter'] && $search_params['filter']['filters'])
			{ 
				$where = '';
				$where_arr = array();
				$i = 0;
				foreach($search_params['filter']['filters'] as $filter_key => $filter_obj)
				{
					if($filter_obj['field'])
					{
						$where_arr[$i] = ' '.$this->getOperatorString($filter_obj);
						$i++;
					}
					else if($filter_obj['filters'])
					{
						$where_sub_arr = array();
						$sub = 0;
						foreach($filter_obj['filters'] as $sub_filter_key => $sub_filter_obj)
						{
							$where_sub_arr[$sub] = ' '.$this->getOperatorString($sub_filter_obj);
							$sub++;
						}
						$where_arr[$i] = ' ('.implode(strtoupper($filter_obj['logic']), $where_sub_arr).') ';
						$i++;
					}
				}
				$where = implode(strtoupper($search_params['filter']['logic']), $where_arr);				
				if(!empty($where))
				{
					$select->where($where);	
				}
			}
		}
		
		$options = $this->fetchAll($select);		
        if (!$options) 
		{
			$options = null;
        }		
        return $options; 			
	}
	
	private function getOperatorString($operator_arr)
	{
		$table_prefix = ($this->isColumnExists($operator_arr['field'])) ? 'm.' : '';
		$field_array = explode('_', $operator_arr['field']);
		if(in_array('date', $field_array))
		{
			$data_arr = preg_split('/[- :]/',$operator_arr['value']); //explode('GMT', $operator_arr['value']);
			if($data_arr[0])
			{
				$time = strtotime($data_arr[0].' '.$data_arr[1].' '.$data_arr[2].' '.$data_arr[3].' '.$data_arr[4].':'.$data_arr[5].':'.$data_arr[6]);			
				$operator_arr['value'] = date("Y-m-d H:i:s", $time);
			}
		}
		
		$operatorFirstPart = '';
		switch($operator_arr['field'])
		{
			case 'full_name' :
					$operatorFirstPart = " CONCAT(".$table_prefix."title, ' ', ".$table_prefix."firstName, ' ', ".$table_prefix."lastName) ";
				break;
			case 'last_access' :
					$data_arr = preg_split('/[- :]/',$operator_arr['value']); 
					if($data_arr[0])
					{
						$time = strtotime($data_arr[0].' '.$data_arr[1].' '.$data_arr[2].' '.$data_arr[3].' '.$data_arr[4].':'.$data_arr[5].':'.$data_arr[6]);			
						$operator_arr['value'] = date("Y-m-d H:i:s", $time);
					}
					$operatorFirstPart = $table_prefix.$operator_arr['field'];
				break;
			default:
				$operatorFirstPart = $table_prefix.$operator_arr['field'];
				break;
		}
		$operatorString= '';
		switch($operator_arr['operator'])
		{
			case 'eq':
					$operatorString = $operatorFirstPart.' = "'.$operator_arr['value'].'" ';
				break;
			case 'neq':
					$operatorString = $operatorFirstPart.' != "'.$operator_arr['value'].'" ';
				break;
			case 'startswith':
					$operatorString = $operatorFirstPart.' LIKE "'.$operator_arr['value'].'%" ';
				break;
			case 'contains':
					$operatorString = $operatorFirstPart.' LIKE "%'.$operator_arr['value'].'%" ';
				break;
			case 'doesnotcontain':
					$operatorString = $operatorFirstPart.' NOT LIKE "%'.$operator_arr['value'].'%" ';
				break;
			case 'endswith':
					$operatorString = $operatorFirstPart.' LIKE "%'.$operator_arr['value'].'" ';
				break;
			case 'gte':
					$operatorString = $operatorFirstPart.' >=  "'.$operator_arr['value'].'" ';
				break;
			case 'gt':
					$operatorString = $operatorFirstPart.' > "'.$operator_arr['value'].'" ';
				break;
			case 'lte':
					$operatorString = $operatorFirstPart.' <= "'.$operator_arr['value'].'" ';
				break;
			case 'lt':
					$operatorString = $operatorFirstPart.' < "'.$operator_arr['value'].'" ';
				break;
			case 'eqy':
					$operatorString = 'YEAR(m.register_date)'.' = "'.$operator_arr['value'].'" ';
				break;
			case 'eqm':
					$operatorString = 'date_format(m.register_date, "%M")'.' = "'.$operator_arr['value'].'" ';
				break;
			case 'eqd':
					$operatorString = 'date_format(m.register_date, "%d")'.' = "'.$operator_arr['value'].'" ';
				break;
			default:
					$operatorString = $operatorFirstPart.' = "'.$operator_arr['value'].'" ';
				break;				
		}
		return $operatorString;
	}
	
	private function isColumnExists($column)
	{
		$this->_cols = ($this->_cols == null) ? $this->info(Zend_Db_Table_Abstract::COLS) : $this->_cols;		
		return in_array($column, $this->_cols);
	}
}

?>