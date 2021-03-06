<?php
namespace GO\Core\Smtp\Controller;

use GO\Core\Controller;
use GO\Core\Smtp\Model\Account;
use IFW\Exception\NotFound;
use IFW\Orm\Query;
use function GO;

/**
 * The controller for the account model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AccountController extends Controller {


	/**
	 * Fetch accounts
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function store($orderColumn = 'fromEmail', $orderDirection = 'ASC', $limit = 0, $offset = 0, $searchQuery = "", $returnProperties = "") {

		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, array('t.fromEmail','t.username'));

		$accounts = Account::find($query);
		$accounts->setReturnProperties($returnProperties);

		$this->renderStore($accounts);
	}
	
	
	/**
	 * Get's the default data for a new account
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function newInstance($returnProperties = ""){
		
		$user = new Account();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of accounts or fetch a single account
	 *
	 * The attributes of this account should be posted as JSON in a account object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $accountId The ID of the account
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function read($accountId = null, $returnProperties = "") {	
		$account = Account::findByPk($accountId);


		if (!$account) {
			throw new NotFound();
		}

		$this->renderModel($account, $returnProperties);
		
	}

	/**
	 * Create a new account. Use GET to fetch the default attributes or POST to add a new account.
	 *
	 * The attributes of this account should be posted as JSON in a account object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($returnProperties = "") {

		$account = new Account();
		$account->setValues(GO()->getRequest()->body['data']);
		$account->save();

		$this->renderModel($account, $returnProperties);
	}

	/**
	 * Update a account. Use GET to fetch the default attributes or POST to add a new account.
	 *
	 * The attributes of this account should be posted as JSON in a account object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"accountname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $accountId The ID of the account
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($accountId, $returnProperties = "") {

		$account = Account::findByPk($accountId);

		if (!$account) {
			throw new NotFound();
		}

		$account->setValues(GO()->getRequest()->body['data']);
		$account->save();

		$this->renderModel($account, $returnProperties);
	}

	/**
	 * Delete a account
	 *
	 * @param int $accountId
	 * @throws NotFound
	 */
	public function delete($accountId) {
		$account = Account::findByPk($accountId);

		if (!$account) {
			throw new NotFound();
		}

		$account->delete();

		$this->renderModel($account);
	}
	
	
	/**
	 * Update multiple records at once with a PUT request.
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
				$record = Account::findByPk($values['id']);

				if (!$record) {
					throw new NotFound();
				}
			}else
			{
				$record = new Account();
			}
			
			$record->setValues($values);
			$record->save();
			
			$response['data'][] = $record->toArray();
		}
		
		$this->render($response);
	}
}

