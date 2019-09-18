<?php

use Journal3\Opencart\Model;
use Journal3\Utils\Arr;

class ModelJournal3Newsletter extends Model {

	public function all($filters = array()) {
		$filter_sql = "";

		if ($filter = Arr::get($filters, 'filter')) {
			$filter_sql .= " WHERE `email` LIKE '%{$this->dbEscape($filter)}%'";
		}

		$order_sql = "ORDER BY email";

		$page = (int)Arr::get($filters, 'page');
		$limit = (int)Arr::get($filters, 'limit');

		if ($page || $limit) {
			if ($page < 1) {
				$page = 1;
			}

			if ($limit < 1) {
				$limit = 10;
			}

			$order_sql .= ' LIMIT ' . (($page - 1) * $limit) . ', ' . $limit;
		}

		$sql = "
			FROM
				`{$this->dbPrefix('journal3_newsletter')}`
				{$filter_sql}						
		";

		$count = (int)$this->db->query("SELECT COUNT(*) AS total {$sql}")->row['total'];

		$result = array();

		if ($count) {
			$query = $this->db->query("
				SELECT
					* 
				{$sql} 
				{$order_sql}
			");

			foreach ($query->rows as $row) {
				$result[] = array(
					'id'       => $row['newsletter_id'],
					'name'     => $row['name'],
					'email'    => $row['email'],
					'ip'       => $row['ip'],
					'store_id' => $row['store_id'],
				);
			}
		}

		return array(
			'count' => $count,
			'items' => $result,
		);
	}

	public function unsubscribe($email) {
		$this->dbQuery("DELETE FROM `{$this->dbPrefix('journal3_newsletter')}` WHERE email = '{$this->dbEscape($email)}'");
	}

}
