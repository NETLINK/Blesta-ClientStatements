<?php

class ClientWidget extends ClientStatementsController {

	public function preAction() {
		
		parent::preAction();
		
		//Language::loadLang("client_tickets", null, PLUGINDIR . "support_manager" . DS . "language" . DS);

		//$this->uses(array("SupportManager.SupportManagerTickets"));
		
		// Restore structure view location of the client portal
		//$this->structure->setDefaultView(APPDIR);
		//$this->structure->setView(null, $this->orig_structure_view);
		
		$this->client_id = $this->Session->read("blesta_client_id");

		$this->set("string", $this->DataStructure->create("string"));
	}
	
	/**
	 * Builds a hash mapping default support ticket priorities to class names
	 *
	 * @return array A key/value array of priority => class name
	 */
	private function getPriorityClasses() {
		return array('low' => "success", 'medium' => "medium", 'high' => "warning", 'critical' => "danger", 'emergency' => "emergency");
	}
	
	/**
	 * View tickets
	 */
	public function index() {
	
		// Only available via AJAX
		if (!$this->isAjax()) {
			//$this->redirect($this->base_uri);
		}
		
		$status = (isset($this->get[0]) ? $this->get[0] : "not_closed");
		$page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
		$sort = (isset($this->get['sort']) ? $this->get['sort'] : "last_reply_date");
		$order = (isset($this->get['order']) ? $this->get['order'] : "desc");
		
		$this->set("status", $status);
		$this->set("sort", $sort);
		$this->set("order", $order);
		$this->set("negate_order", ($order == "asc" ? "desc" : "asc"));
		
		// Set the number of clients of each type
		$status_count = array();
		
		//$tickets = $this->SupportManagerTickets->getList($status, null, $this->client_id, $page, array($sort => $order), false);
		//$total_results = $this->SupportManagerTickets->getListCount($status, null, $this->client_id);
		
		// Overwrite default pagination settings
		/*
		$settings = array_merge(Configure::get("Blesta.pagination_client"), array(
				'total_results' => $total_results,
				'uri'=>$this->base_uri . "plugin/support_manager/client_tickets/index/" . $status . "/[p]/",
				'params'=>array('sort'=>$sort,'order'=>$order)
			)
		);
		$this->helpers(array("Pagination"=>array($this->get, $settings)));
		$this->Pagination->setSettings(Configure::get("Blesta.pagination_ajax"));
		
		// Set the last reply time
		foreach ($tickets as &$ticket)
			$ticket->last_reply_time = $this->timeSince($ticket->last_reply_date);
		
		$this->set("tickets", $tickets);
		$this->set("status_count", $status_count);
		$this->set("priorities", $this->SupportManagerTickets->getPriorities());
		$this->set("statuses", $this->SupportManagerTickets->getStatuses());
		$this->set("priority_classes", $this->getPriorityClasses());
		*/
		if ($this->isAjax()) {
			return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : (isset($this->get[1]) || isset($this->get['sort'])));
		}
	}
	
}
