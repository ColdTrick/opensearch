<?php

namespace ColdTrick\OpenSearch\Di;

use Elgg\Database\Delete;
use Elgg\Database\Select;
use Elgg\Database\Update;
use Elgg\Queue\DatabaseQueue;
use Elgg\Traits\Di\ServiceFacade;

/**
 * Extension of the Elgg database queue to enable multi-dequeue
 * and order de dequeue by timestamp
 */
class DeleteQueue extends DatabaseQueue {
	
	use ServiceFacade;
	
	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct('opensearch', _elgg_services()->db);
	}
	
	/**
	 * Returns registered service name
	 *
	 * @return string
	 */
	public static function name(): string {
		return 'opensearch.databasequeue';
	}
	
	/**
	 * {@inheritdoc}
	 *
	 * @param int $number number of items to dequeue
	 */
	public function dequeue(int $number = 100) {
		// get a record for processing
		$select = Select::fromTable(self::TABLE_NAME);
		$select->select('*')
			->where($select->compare('name', '=', $this->name, ELGG_VALUE_STRING))
			->andWhere($select->expr()->isNull('worker'))
			->andWhere($select->compare('timestamp', '<', $this->getCurrentTime()->getTimestamp(), ELGG_VALUE_TIMESTAMP))
			->orderBy('timestamp', 'ASC')
			->setMaxResults($number);
		
		$rows = $this->db->getData($select);
		if (empty($rows)) {
			return;
		}
		
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) $row->id;
		}
		
		// lock a record for processing
		$update = Update::table(self::TABLE_NAME);
		$update->set('worker', $update->param($this->workerId, ELGG_VALUE_STRING))
			->where($update->compare('name', '=', $this->name, ELGG_VALUE_STRING))
			->andWhere($update->compare('id', 'in', $ids, ELGG_VALUE_ID))
			->andWhere($update->expr()->isNull('worker'));
		
		if ($this->db->updateData($update, true) < 1) {
			return;
		}
		
		// remove locked record from database
		$delete = Delete::fromTable(self::TABLE_NAME);
		$delete->where($delete->compare('id', 'in', $ids, ELGG_VALUE_ID));
		
		$this->db->deleteData($delete);
		
		// build result set
		$result = [];
		foreach ($rows as $row) {
			$result[] = unserialize($row->data);
		}
		
		return $result;
	}
}
