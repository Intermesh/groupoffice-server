<?php

namespace GO\Core\Users\Controller;

use GO\Core\Controller;
use GO\Core\Users\Model\GroupFilter;
use GO\Core\Users\Model\User;
use IFW\Data\Filter\FilterCollection;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for users. Admin group is required.
 * 
 * Uses the {@see User} model.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class UserController extends Controller {

	private function getFilterCollection() {
		$fc = new FilterCollection(User::class);
		$fc->addFilter(GroupFilter::class);

		return $fc;
	}

	public function filters() {
		$this->render($this->getFilterCollection()->toArray());
	}

	/**
	 * Fetch users
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @param string $where {@see \IFW\Db\Criteria::whereSafe()}
	 * @return array JSON Model data
	 */
	public function store($orderColumn = 'username', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new Query())
						->orderBy([$orderColumn => $orderDirection])
						->limit($limit)
						->offset($offset);

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['t.username']);
		}

		$this->getFilterCollection()->apply($query);

		if(isset($q)) {
			$query->setFromClient($q);
		}

		$users = User::find($query);
		$users->setReturnProperties($returnProperties);
		$this->renderStore($users);
	}

	/**
	 * GET a list of users or fetch a single user
	 *
	 * 
	 * @param int $userId The ID of the group
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function read($userId = null, $returnProperties = "") {

		if ($userId === "current") {
			$user = \GO()->getAuth()->user();
		} else {
			$user = User::findByPk($userId);
		}

		if (!$user) {
			throw new NotFound();
		}

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * Get's the default data for a new user
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function newInstance($returnProperties = "") {

		$user = new User();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * Create a new field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"fieldname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($returnProperties = "") {

		$user = new User();
		$user->setValues(GO()->getRequest()->body['data']);
		$user->save();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * Update a field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"fieldname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $userId The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($userId, $returnProperties = "") {

		if ($userId === "current") {
			$user = \GO()->getAuth()->user();
		} else {
			$user = User::findByPk($userId);
		}

		if (!$user) {
			throw new NotFound();
		}

		$user->setValues(GO()->getRequest()->body['data']);

		if ($user->isModified('password')) {

			if (!\GO()->getAuth()->isAdmin() && !$user->checkPassword($user->currentPassword)) {
				throw new \IFW\Auth\Exception\BadLogin();
			}
		}

		$user->save();


		$this->renderModel($user, $returnProperties);
	}

	/**
	 * Delete a field
	 *
	 * @param int $userId
	 * @throws NotFound
	 */
	public function delete($userId) {
		$user = User::findByPk($userId);

		if (!$user) {
			throw new NotFound();
		}

		$user->delete();

		$this->renderModel($user);
	}

	/**
	 * Change the user its password if current password is provided correctly
	 * @param int $userId
	 * @throws NotFound
	 */
	public function changePassword($userId) {
		$user = User::findByPk($userId);
		if (!$user) {
			throw new NotFound();
		}
		if ($user->checkPassword(GO()->getRequest()->body['currentPassword'])) {
			$user->password = GO()->getRequest()->body['password'];
			$user->forcePasswordChange = false; // Set to false if password is changed
		}
		$user->save();
		$this->renderModel($user);
	}
	
		/**
	 * Update multiple contacts at once with a PUT request.
	 * 
	 * @example multi delete
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 *	"data" : [{"id" : 1, "markDeleted" : true}, {"id" : 2, "markDeleted" : true}]
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * @throws NotFound
	 */
	public function multiple() {
		
		$response = ['data' => []];
		
		foreach(GO()->getRequest()->getBody()['data'] as $values) {
			
			if(!empty($values['id'])) {
				$user = User::findByPk($values['id']);

				if (!$user) {
					throw new NotFound();
				}
			}else
			{
				$user = new User();
			}
			
			$user->setValues($values);
			$user->save();
			
			$response['data'][] = $user->toArray('id');
		}
		
		$this->render($response);
	}

//	/**
//	 * 
//	 * @param string $email
//	 * @throws NotFound
//	 */
//	public function forgotPassword($email) {
//		$user = User::find(['OR','LIKE', ['email'=>$email, 'emailSecondary'=>$email]])->single();
//		
//		if (!$user) {
//			throw new NotFound();
//		}
//		
//		
//		$message = new Message(
// 						GO()->getSettings()->smtpAccount, 
// 						GO()->getRequest()->getBody()['subject'], 
// 						GO()->getRequest()->getBody()['body'],
//						'text/html');
// 		
// 		$message->setTo($email);
// 		
// 		$numberOfRecipients = $message->send();
//
//		$this->render(['success' => $numberOfRecipients === 1]);
//		
//	}
}
