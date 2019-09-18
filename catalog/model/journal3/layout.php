<?php

use Journal3\Opencart\Model;
use Journal3\Utils\Arr;

class ModelJournal3Layout extends Model {

	public function get($id) {
		$query = $this->db->query("
			SELECT
				layout_id,
				layout_data
			FROM
				`{$this->dbPrefix('journal3_layout')}`
			WHERE 
				`layout_id` = '{$this->dbEscapeInt($id)}'
				OR `layout_id` = -1
			ORDER BY
				`layout_id` DESC
		");

		if ($query->num_rows === 0) {
			return array();
		}

		$result = array();

		foreach ($query->rows as $row) {
			if ($row['layout_id'] > 0) {
				$data = $this->decode($row['layout_data'], true);
			} else {
				$data = array(
					'positions' => array(
						'global' => $this->decode($row['layout_data'], true),
					),
				);
			}

			$result = Arr::merge($result, $data);
		}

		return $result;
	}

}
