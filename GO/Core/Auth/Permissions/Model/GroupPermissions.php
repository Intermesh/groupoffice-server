<?php

namespace GO\Core\Auth\Permissions\Model;

use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;

/**
 * Group permissions model
 * 
 * You can use this model if you want to limit access to an item and grant 
 * access to one or more groups.
 * 
 * 1. Create a link table between your record and {@see \GO\Core\Users\Model\Group}. 
 * 2. Create the record model and make this record extend {@see GroupAccess}. 
 *		For example see {@see \GO\Modules\GroupOffice\Contacts\Model\ContactGroup}
 * 3. Use the this model in the record that you want to secure. For example see 
 *		{@see \GO\Modules\GroupOffice\Contacts\Model\Contact::internalGetPermissions()}
 *		``````````````````````````````````````````````````````````````````````````
 *		protected static function internalGetPermissions() {
 *		  return new \GO\Core\Auth\Permissions\Model\GroupPermissions(ContactGroup::class);
 *	  }	
 * 		``````````````````````````````````````````````````````````````````````````
 * 4. Define a 'groups' relation to the record in the record you 
 *		want to secure in function defineRelations():
 *		``````````````````````````````````````````````````````````````````````````
 *		self::hasMany('groups', ContactGroup::class, ['id' => 'contactId']);
 *		``````````````````````````````````````````````````````````````````````````
 * 5. By default there is a 'write' property. If you don't add this
 *		field to your database 'write' access will always be false.
 * 
 */
class GroupPermissions extends Model {	
		
	private $groupAccess;
	
	private $groupAccessRecordName;
	
	private $groupAccessKey;
	
	
	/**
	 * Constructor
	 * 
	 * @param string $groupAccessRecordName The link table record
	 */
	public function __construct($groupAccessRecordName, $key = 'id') {		
		$this->groupAccessRecordName = $groupAccessRecordName;
		$this->groupAccessKey = $key;
		parent::__construct();
	}
	
	private function getGroupAccessRecordName() {
		return $this->groupAccessRecordName;
	}
	
	protected function internalCan($permissionType, UserInterface $user) {		
		switch($permissionType) {
			case self::PERMISSION_CREATE:
				return true;
				
			case self::PERMISSION_READ:
				return $this->getGroupAccess($user) != false;			
				
			case self::PERMISSION_WRITE:
				return $this->getGroupAccess($user) != false && $this->getGroupAccess($user)->write;
				
			case self::PERMISSION_MANAGE:
				return $this->record->ownedBy == $user->group->id; //owner
					
			default:
				return false;
		}

		return false;
	}
	
	private function getGroupAccess($user) {
		if(!isset($this->groupAccess)) {
			
			$cls = $this->getGroupAccessRecordName();
			
			return $this->groupAccess = $cls::find((new Query())
							->joinRelation('groupUsers')
							->andWhere([$cls::getForPk() => $this->record->{$this->groupAccessKey}])							
							->andWhere(['groupUsers.userId' => $user->id()])
							)->single();
		}
		
		return $this->groupAccess;
	}
	
	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		
		$cls = $this->getGroupAccessRecordName();		
		
		$subQuery = (new Query())
						->tableAlias('groupAccess')						
						->joinRelation('groupUsers')
						->where(['groupUsers.userId' => $user->id()])
						->andWhere('groupAccess.'.$cls::getForPk().' = '.$query->getTableAlias(). '.' . $this->groupAccessKey);
		
		switch($query->getRequirePermissionType()) {
			case self::PERMISSION_WRITE:
				$subQuery->andWhere(['groupAccess.write' => true]);
				
				$groupAccess = $cls::find($subQuery);
				break;
			
			case null:
			case self::PERMISSION_READ:
				$groupAccess = $cls::find($subQuery);
				break;
			
			case self::PERMISSION_MANAGE:
				
				$groupAccess = \GO\Core\Users\Model\UserGroup::find(
						(new Query())
						->tableAlias('ug')
						->where(['userId' => $user->id()])
						->andWhere('ug.groupId = '.$query->getTableAlias().'.ownedBy')
						);
				
				break;
			
			default:				
				throw new \Exception("Invalid permission required");
		}
		
		
		
		$query->allowPermissionTypes([\IFW\Auth\Permissions\Model::PERMISSION_READ])
						->andWhere(['EXISTS', $groupAccess]);
	}

}
