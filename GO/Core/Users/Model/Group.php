<?php
namespace GO\Core\Users\Model;

use GO\Core\Users\Model\Group;
use GO\Core\Users\Model\UserGroup;
use GO\Core\Modules\Model\ModuleGroup;
use IFW\Auth\Permissions\ReadOnly;
use IFW\Orm\Record;
use IFW\Orm\Relation;


/**
 * Groups are used for permissions
 *
 *
 * @property User $users The users in this group
 * @properry User $user If this group represents a user then this returns the user
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Group extends Record{
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * 
	 * @var bool
	 */							
	public $autoAdd = false;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var int
	 */							
	public $userId;

	/**
	 * The ID of the admins group
	 */
	const ID_ADMINS = 1;

	/**
	 * The ID of the Everyone group
	 */
	const ID_INTERNAL = 2;
	

	protected static function defineRelations() {		
		self::hasMany('users', User::class, ['id' => 'groupId'])
						->via(UserGroup::class, ['userId'=>'id']);
		
		self::hasMany('userGroup', UserGroup::class, ['id'=>'groupId']);
		self::hasMany('moduleGroups', ModuleGroup::class, ['id' => 'groupId']);
		
		self::hasMany('accountGroups', \GO\Core\Accounts\Model\AccountGroup::class, ['id' => 'groupId']);
		
		self::hasOne('user', User::class, ['userId'=>'id'])
						->setDeleteAction(Relation::DELETE_RESTRICT);		
	
		parent::defineRelations();
	}
	
	protected static function internalGetPermissions() {
		return new ReadOnly();
	}
	
	public static function tableName() {
		return 'auth_group';
	}
	
	/**
	 * Check if this group has the user
	 * 
	 * @param int $userId
	 * @return boolean
	 */
	public function hasUser($userId) {
		return $this->userGroup(['userId' => $userId])->single() != false;
	}

	/**
	 * Get the administrator's group
	 *
	 * @return Group
	 */
	public static function findAminGroup(){

		$group = Group::findByPk(self::ID_ADMINS);

		if(!$group){
			$group = new Group();
			$group->id=self::ID_ADMINS;
			$group->userId=1;
			$group->name='Admins';
			$group->save();
		}

		return $group;
	}

	/**
	 * Get the everyone group
	 *
	 * @return Group
	 */
	public static function findInternalGroup(){

		$group = Group::findByPk(self::ID_INTERNAL);

		if(!$group){
			$group = new Group();
			$group->id=self::ID_INTERNAL;
			$group->name='Everyone';
			$group->save();
		}

		return $group;
	}
}