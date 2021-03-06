<?php

namespace GO\Core\CustomFields\Controller;

use GO\Core\Controller;
use GO\Core\CustomFields\Model\Field;
use GO\Core\CustomFields\Model\FieldSet;
use IFW\Exception\NotFound;
use IFW\Orm\Query;
use function GO;

/**
 * The controller for fieldSets. Admin group is required.
 * 
 * Uses the {@see FieldSet} model.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class FieldSetController extends Controller {
	
	public function checkAccess() {
		return true;
	}

	/**
	 * Fetch fieldSets
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	
	 * @return array JSON Model data
	 */
	public function store($modelName, $orderColumn = 'sortOrder', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "") {

		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset);

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['t.name']);
		}
		
		$query->where(['modelName' => $modelName]);

//		if (!empty($where)) {
//
//			$where = json_decode($where, true);
//
//			if (count($where)) {
//				$query
//						->groupBy(['t.id'])
//						->whereSafe($where);
//			}
//		}
		GO()->getAuth()->sudo(function() use($query, $returnProperties) {
			$fieldSets = FieldSet::find($query);
			$fieldSets->setReturnProperties($returnProperties);

			$this->renderStore($fieldSets);
		});
		
	}

	/**
	 * GET a list of fieldSets or fetch a single fieldSet
	 *
	 * 
	 * @param int $fieldSetId The ID of the group
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function read($fieldSetId = null, $returnProperties = "") {

		$fieldSet = FieldSet::findByPk($fieldSetId);

		if (!$fieldSet) {
			throw new NotFound();
		}

		$this->renderModel($fieldSet, $returnProperties);
	}

	/**
	 * Get's the default data for a new fieldSet
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function newInstance($modelName, $returnProperties = "") {

		$fieldSet = new FieldSet();
		$fieldSet->modelName = $modelName;

		$this->renderModel($fieldSet, $returnProperties);
	}

	/**
	 * Create a new fieldSet. Use GET to fetch the default attributes or POST to add a new fieldSet.
	 *
	 * The attributes of this fieldSet should be posted as JSON in a fieldSet object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"fieldSetname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($modelName, $returnProperties = "") {

		$fieldSet = new FieldSet();
		$fieldSet->modelName = $modelName;
		$fieldSet->setValues(GO()->getRequest()->body['data']);
		$fieldSet->save();

		$this->renderModel($fieldSet, $returnProperties);
	}

	/**
	 * Update a fieldSet. Use GET to fetch the default attributes or POST to add a new fieldSet.
	 *
	 * The attributes of this fieldSet should be posted as JSON in a fieldSet object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"fieldSetname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $fieldSetId The ID of the fieldSet
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($fieldSetId, $returnProperties = "") {

		$fieldSet = FieldSet::findByPk($fieldSetId);

		if (!$fieldSet) {
			throw new NotFound();
		}

		$fieldSet->setValues(GO()->getRequest()->body['data']);

		$fieldSet->save();


		$this->renderModel($fieldSet, $returnProperties);
	}

	/**
	 * Delete a fieldSet
	 *
	 * @param int $fieldSetId
	 * @throws NotFound
	 */
	public function delete($fieldSetId) {
		$fieldSet = FieldSet::findByPk($fieldSetId);

		if (!$fieldSet) {
			throw new NotFound();
		}

		$fieldSet->delete();

		$this->renderModel($fieldSet);
	}



	public function test(){
		
		$fieldSet = FieldSet::find(['name' => 'Tennis'])->single();
		if($fieldSet){
			$fieldSet->delete();
		}
			$fieldSet = new FieldSet();
			$fieldSet->modelName = "\GO\Modules\Contacts\Model\ContactCustomFields";
			$fieldSet->name = "Tennis";
			$success = $fieldSet->save();

			if(!$success){
				var_dump($fieldSet->getValidationErrors());
				exit();
			}
		
		
		$field = Field::find(['databaseName' =>  "Een tekstveld"])->single();	
		if(!$field){
			$field = new Field();
		}
		$field->fieldSet = $fieldSet;
		$field->name = "Een tekstveld";
		$field->type = Field::TYPE_TEXT;
		$field->databaseName = "Een tekstveld";
		$field->data = ['maxLength' => 100];
		$field->placeholder = "De placeholder...";
		if(!$field->save()){
			var_dump($field->getValidationErrors());
			exit();
		}
		
		$field = Field::find(['databaseName' =>  "Een textarea"])->single();	
		if(!$field){
			$field = new Field();
		}
		$field->fieldSet = $fieldSet;
		$field->name = "Een textarea";
		$field->type = Field::TYPE_TEXTAREA;
		$field->databaseName = "Een textarea";		
		$field->placeholder = "De placeholder...";
		if(!$field->save()){
			var_dump($field->getValidationErrors());
			exit();
		}
		
		$field = Field::find(['databaseName' =>  "zaterdagInvaller"])->single();	
		if(!$field){
			$field = new Field();
		}
		$field->fieldSet = $fieldSet;
		$field->name = "Ik wil invallen op zaterdag";
		$field->type = Field::TYPE_CHECKBOX;
		$field->databaseName = "zaterdagInvaller";
		$field->placeholder = "De placeholder...";
		if(!$field->save()){
			var_dump($field->getValidationErrors());
			exit();
		}
		
		$field = Field::find(['databaseName' =>  "Speelsterkte enkel"])->single();	
		if(!$field){
			$field = new Field();
		}
		$field->fieldSet = $fieldSet;
		$field->name = "Speelsterkte enkel";
		$field->type = Field::TYPE_SELECT;
		$field->databaseName = "Speelsterkte enkel";		
		$field->placeholder = "De placeholder...";
		$field->data = ['options' => ["9","8","7","6","5","4","3","2","1"]];
		$field->defaultValue = "9";
		if(!$field->save()){
			var_dump($field->getValidationErrors());
			exit();
		}
		
		$field = Field::find(['databaseName' =>  "Speelsterkte dubbel"])->single();	
		if(!$field){
			$field = new Field();
		}
		$field->fieldSet = $fieldSet;
		$field->name = "Speelsterkte dubbel";
		$field->type = Field::TYPE_SELECT;
		$field->databaseName = "Speelsterkte dubbel";
		$field->placeholder = "De placeholder...";
		$field->data = ['options' => ["9","8","7","6","5","4","3","2","1"]];
		$field->defaultValue = "9";
		if(!$field->save()){
			var_dump($field->getValidationErrors());
			exit();
		}
		
		
		$field = Field::find(['databaseName' =>  "Lid sinds"])->single();	
		if(!$field){
			$field = new Field();
		}
		$field->fieldSet = $fieldSet;
		$field->name = "Lid sinds";
		$field->type = Field::TYPE_DATE;
		$field->databaseName = "Lid sinds";		
		$field->placeholder = "De placeholder...";
		if(!$field->save()){
			var_dump($field->getValidationErrors());
			exit();
		}	

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
				$fieldSet = FieldSet::findByPk($values['id']);

				if (!$fieldSet) {
					throw new NotFound();
				}
			}else
			{
				$fieldSet = new FieldSet();
			}
			
			$fieldSet->setValues($values);
			$fieldSet->save();
			
			$response['data'][] = $fieldSet->toArray('id');
		}
		
		$this->render($response);
	}

}
