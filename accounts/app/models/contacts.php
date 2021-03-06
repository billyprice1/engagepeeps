<?php
/**
 * Contact management
 * 
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Contacts extends AppModel {
	
	/**
	 * Initialize Contacts
	 */
	public function __construct() {
		parent::__construct();
		Language::loadLang(array("contacts"));
	}
	
	/**
	 * Add a contact with the fields given
	 *
	 * @param array $vars An array of variable contact info, including
	 * 	- client_id The client ID this contact will be associated with
	 * 	- user_id The user ID this contact belongs to if this contact has their own unique user record (optional)
	 * 	- contact_type The type of contact, either 'primary' (default), 'billing', or 'other' (optional)
	 * 	- contact_type_id The ID of the contact type if contact_type is 'other' (optional)
	 * 	- first_name The first name of this contact
	 * 	- last_name The last name of this contact
	 * 	- title The business title for this contact (optional)
	 * 	- company The company/organization this contact belongs to (optional)
	 * 	- email This contact's email address
	 * 	- address1 This contact's address (optional)
	 * 	- address2 This contact's address line two (optional)
	 * 	- city This contact's city (optional)
	 * 	- state The 3-character ISO 3166-2 subdivision code, requires country (optional)
	 * 	- zip The zip/postal code for this contact (optional)
	 * 	- country The 2-character ISO 3166-1 country code, required if state is given (optional)
	 * 	- numbers An array of number data including (optional):
	 * 		- number The phone number to add
	 * 		- type The type of phone number 'phone', 'fax' (optional, default 'phone')
	 * 		- location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
	 * @return int The contact ID, void on error
	 * @see Contacts::addNumber()
	 */
	public function add(array $vars) {
		$rules = $this->getAddRules($vars);
		
		// Check for optional numbers data
		if (isset($vars['numbers'])) {
			$multiple_numbers = false;
			if (is_array($vars['numbers']))
				$multiple_numbers = true;
			
			$number_rules = $this->getNumberRules($multiple_numbers);
			
			// Put our rules together into one set
			$rules = array_merge($rules, $number_rules);
		}
		
		$this->Input->setRules($rules);
		
		if ($this->Input->validates($vars)) {
			$fields = array("client_id", "user_id", "contact_type", "contact_type_id", "first_name",
				"last_name", "title", "company", "email", "address1", "address2", "city", "state",
				"zip", "country", "date_added"
			);
			
			$vars['date_added'] = date("Y-m-d H:i:s");
			
			// Add contact
			$this->Record->insert("contacts", $vars, $fields);
			
			$contact_id = $this->Record->lastInsertId();
			
			// Add contact numbers
			if (!empty($vars['numbers'])) {
				if (is_array($vars['numbers'])) {
					// Update multiple numbers
					foreach ($vars['numbers'] as $number) {
						// Ignore a case that neither ID nor number have been set
						if (empty($number['id']) && empty($number['number']))
							continue;
						
						$this->addNumber($contact_id, $number);
					}
				}
			}
			
			return $contact_id;
		}
	}

	/**
	 * Edit the contact with the fields given, all fields optional
	 * 
	 * @param int $contact_id The contact ID to update
	 * @param array $vars An array of variable contact info, including
	 * 	- contact_type The type of contact, either "primary" (default) or "other" (optional)
	 * 	- contact_type_id The ID of the contact type if contact_type is "other" (optional)
	 * 	- first_name The first name of this contact
	 * 	- last_name The last name of this contact
	 * 	- title The business title for this contact (optional)
	 * 	- company The company/organization this contact belongs to (optional)
	 * 	- email This contact's email address
	 * 	- address1 This contact's address (optional)
	 * 	- address2 This contact's address line two (optional)
	 * 	- city This contact's city (optional)
	 * 	- state The 3-character ISO 3166-2 subdivision code, requires country (optional)
	 * 	- zip The zip/postal code for this contact (optional)
	 * 	- country The 2-character ISO 3166-1 country code, required if state is given (optional)
	 * 	- numbers An array of number data including (optional):
	 * 		- id The ID of the contact number to update (if empty, will add as new)
	 * 		- number The phone number to add (if empty, will remove this record)
	 * 		- type The type of phone number 'phone', 'fax' (optional, default 'phone')
	 * 		- location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
	 * @return stdClass object represented the updated contact if successful, void otherwise
	 * @see Contacts::addNumber()
	 * @see Contacts::editNumber()
	 */	
	public function edit($contact_id, array $vars) {
		$old_contact = $this->get($contact_id);
		
		$vars['contact_id'] = $contact_id;
		$vars['client_id'] = $this->ifSet($old_contact->client_id);
		$rules = $this->getAddRules($vars);
		
		// Validate the contact ID exists
		$rules['contact_id'] = array(
			'exists' => array(
				'rule' => (boolean)$old_contact,
				'message' => $this->_("Contacts.!error.contact_id.exists")
			)
		);
		// Validate the contact can be updated based on its status to receive invoices
		$rules['contact_type']['inv_address_to'] = array(
			'if_set' => true,
			'rule' => array(array($this, "validateReceivesInvoices"), $this->ifSet($contact_id, null)),
			'negate' => true,
			'message' => $this->_("Contacts.!error.contact_type.inv_address_to")
		);
		
		// Remove client and user ID constraints
		unset($rules['client_id'], $rules['user_id']);
		
		// Check for optional numbers data
		if (!empty($vars['numbers'])) {
			$multiple_numbers = false;
			if (is_array($vars['numbers']))
				$multiple_numbers = true;
			
			$number_rules = $this->getNumberRules($multiple_numbers, true, false);
			
			// Put our rules together into one set
			$rules = array_merge($rules, $number_rules);
		}
		
		$this->Input->setRules($rules);
		
		if ($this->Input->validates($vars)) {
			$old_contact = (array)$old_contact;
			
			$fields = array("contact_type", "contact_type_id", "first_name", "last_name",
				"title", "company", "email", "address1", "address2", "city", "state", "zip", "country"
			);
			
			// Update contact
			$this->Record->where("id", "=", $contact_id)->update("contacts", $vars, $fields);
			
			// Update/Delete contact numbers
			if (!empty($vars['numbers'])) {
				if (is_array($vars['numbers'])) {
					// Update multiple numbers
					foreach ($vars['numbers'] as $number) {
						// Ignore a case that neither ID nor number have been set
						if (empty($number['id']) && empty($number['number']))
							continue;
						
						// Delete the number if the number is empty
						if (empty($number['number'])) {
							$this->deleteNumber($number['id']);
							continue;
						}
						
						// Add a new number if ID is empty, otherwise update an existing one
						if (empty($number['id']))
							$this->addNumber($contact_id, $number);
						else
							$this->editNumber($number['id'], $number);
					}
				}
			}
			
			$new_contact = $this->get($contact_id);
			
			// Calculate the changes made to the contact and log those results
			$diff = array_diff_assoc($old_contact, (array)$new_contact);
			$fields = array();
			foreach ($diff as $key => $value) {
				$fields[$key]['prev'] = $value;
				$fields[$key]['cur'] = $new_contact->$key;
			}
			
			if (!empty($fields)) {
				if (!isset($this->Logs))
					Loader::loadModels($this, array("Logs"));
				$this->Logs->addContact(array('contact_id'=>$contact_id,'fields'=>$fields));
			}
			
			return $new_contact;
		}
	}
	
	/**
	 * Permanently removes this contact from the system.
	 *
	 * @param int $contact_id The ID of the contact to remove from the system
	 */
	public function delete($contact_id) {
		$vars = array('contact_id' => $contact_id, 'contact_type' => null);
		
		$this->Input->setRules(array(
			'contact_id' => array(
				'primary' => array(
					'rule' => array(array($this, "validateIsPrimary")),
					'negate' => true,
					'message' => $this->_("Contacts.!error.contact_id.primary")
				)
			),
			'contact_type' => array(
				'inv_address_to' => array(
					'rule' => array(array($this, "validateReceivesInvoices"), $contact_id),
					'negate' => true,
					'message' => $this->_("Contacts.!error.contact_type.inv_address_to")
				)
			)
		));
		
		if ($this->Input->validates($vars)) {
			// Remove contact and contact numbers
			$this->Record->from("contacts")->
				leftJoin("contact_numbers", "contact_numbers.contact_id", "=", "contacts.id", false)->
				where("contacts.id", "=", $contact_id)->delete(array("contacts.*", "contact_numbers.*"));
		}
	}
	
	/**
	 * Fetch the contact with the given contact ID
	 *
	 * @param int $contact_id The contact ID to fetch
	 * @return mixed A stdClass contact object, or false if the contact does not exist
	 */
	public function get($contact_id) {
		$fields = array("contacts.*", "contact_types.name"=>"contact_type_name", "contact_types.is_lang"=>"contact_type_is_lang");
		
		return $this->Record->select($fields)->from("contacts")->
			leftJoin("contact_types", "contact_types.id", "=", "contacts.contact_type_id")->
			where("contacts.id", "=", $contact_id)->fetch();
	}
	
	/**
	 * Fetches a list of all contacts under a client
	 *
	 * @param int $client_id The client ID to fetch contacts for
	 * @param int $page The page to return results for
	 * @param string $sort_by The field to sort by
	 * @param string $order The direction to order results
	 * @return mixed An array of objects or false if no results.
	 */
	public function getList($client_id, $page=1, array $order=array('last_name'=>"asc", 'first_name'=>"asc")) {
		$this->Record = $this->getContacts($client_id);
		
		// Return the results
		return $this->Record->order($order)->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
	}
	
	/**
	 * Return the total number of contacts returned from Contacts::getList(),
	 * useful in constructing pagination for the getList() method.
	 *
	 * @param int $client_id The client ID to fetch contacts for
	 * @return int The total number of clients
	 * @see Companies::getList()
	 */
	public function getListCount($client_id) {
		$this->Record = $this->getContacts($client_id);
		
		// Return the number of results
		return $this->Record->numResults();
	}
	
	/**
	 * Fetches a list of all contacts under a client
	 *
	 * @param int $client_id The client ID to fetch contacts for
	 * @param string $contact_type The contact type to fetch (i.e. "primary", "billing", or "other") (optional, default all contact types)
	 * @param array $order The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
	 * @return array A list of stdClass objects representing each contact
	 */
	public function getAll($client_id, $contact_type=null, array $order=array('last_name'=>"asc", 'first_name'=>"asc")) {
		$this->Record = $this->getContacts($client_id);
		
		// Include a specific contact type
		if ($contact_type != null)
			$this->Record->where("contact_type", "=", $contact_type);
		
		return $this->Record->order($order)->fetchAll();
	}
	
	/**
	 * Partially constructs the query required by both Contacts::getList() and
	 * Contacts::getListCount()
	 *
	 * @param int $client_id The client ID to fetch contacts for
	 * @return Record The partially constructed query Record object
	 */
	private function getContacts($client_id) {
		$fields = array("id", "client_id", "user_id", "contact_type",
			"contact_type_id", "first_name", "last_name", "title", "company",
			"email", "address1", "address2", "city", "state", "zip", "country");
		
		$this->Record->select($fields)->from("contacts")->where("client_id","=",$client_id);
		
		return $this->Record;
	}
	
	/**
	 * Retrieves a list of client contact types
	 *
	 * @return array Key=>value pairs of client contact types
	 */
	public function getContactTypes() {
		return array(
			'primary'=>$this->_("Contacts.getcontacttypes.primary"),
			'billing'=>$this->_("Contacts.getcontacttypes.billing"),
			'other'=>$this->_("Contacts.getcontacttypes.other")
		);
	}
	
	/**
	 * Retrieve a single contact type
	 *
	 * @param int $contact_type_id The contact type ID
	 * @return mixed An stdClass object representing the contact type, or false if none exist
	 */
	public function getType($contact_type_id) {
		$contact_type = $this->Record->select(array("id","company_id","name","is_lang"))->from("contact_types")->
			where("id", "=", $contact_type_id)->fetch();
		
		if ($contact_type) {
			// Set a real_name to the language definition, if applicable
			if ($contact_type->is_lang == "1")
				$contact_type->real_name = $this->_("_ContactTypes." . $contact_type->name, true);
			else
				$contact_type->real_name = $contact_type->name;
		}
		
		return $contact_type;
	}
	
	/**
	 * Return all existing contact types in the system for the given company
	 *
	 * @param int $company_id The company ID to fetch contact types for (optional)
	 * @return array An array of stdClass objects representing contact types
	 */
	public function getTypes($company_id=null) {
		$this->Record->select(array("id","company_id","name","is_lang"))->from("contact_types");
		
		if ($company_id != null)
			$this->Record->where("company_id", "=", $company_id);
		
		$contact_types = $this->Record->fetchAll();
		
		// Set a real_name to the language definition, if applicable
		foreach ($contact_types as &$contact_type) {
			if ($contact_type->is_lang == "1")
				$contact_type->real_name = $this->_("_ContactTypes." . $contact_type->name, true);
			else
				$contact_type->real_name = $contact_type->name;
		}
		
		return $contact_types;
	}
	
	/**
	 * Add a contact type
	 *
	 * @param array $vars An array of contact type information including:
	 * 	- name The name of this contact type
	 * 	- is_lang Whether or not 'name' is a language definition
	 * 	- company_id The company ID to add the contact type under (optional)
	 * @return int The contact type ID created, void if error
	 */
	public function addType(array $vars) {
		// Set company ID
		$vars['company_id'] = empty($vars['company_id']) ? null : $vars['company_id'];
		
		$this->Input->setRules($this->getTypeRules());
		
		if ($this->Input->validates($vars)) {
			// Add the type
			$fields = array("company_id", "name", "is_lang");
			$this->Record->insert("contact_types", $vars, $fields);
			return $this->Record->lastInsertId();
		}
	}
	
	/**
	 * Update the contact type with the given data
	 *
	 * @param int $contact_type_id The contact type ID to update
	 * @param array $vars An array of contact type information including:
	 * 	- name The name of this contact type
	 * 	- is_lang Whether or not 'name' is a language definition
	 */
	public function editType($contact_type_id, array $vars) {
		$rules = $this->getTypeRules();
		$rules['contact_type_id'] = array(
			'exists' => array(
				'rule' => array(array($this, "validateExists"), "id", "contact_types"),
				'message' => $this->_("Contacts.!error.contact_type_id.exists")
			)
		);
		
		// Remove company_id constraint
		unset($rules['company_id']);
		
		$this->Input->setRules($rules);
		
		$vars['contact_type_id'] = $contact_type_id;
		
		if ($this->Input->validates($vars)) {
			// Update the type
			$fields = array("name", "is_lang");
			$this->Record->where("id", "=", $contact_type_id)->update("contact_types", $vars, $fields);
		}
	}
	
	/**
	 * Delete a contact type and reset the type for all affected contacts to null
	 *
	 * @param int $contact_type_id The contact type ID
	 */
	public function deleteType($contact_type_id) {
		// Set all contacts with this contact type ID to have a contact type ID of null
		$this->Record->where("contact_type_id", "=", $contact_type_id)->
			update("contacts", array("contact_type_id"=>null));
		
		// Finally delete the contact type
		$this->Record->from("contact_types")->where("id", "=", $contact_type_id)->delete();
	}
	
	/**
	 * Adds a new number
	 *
	 * @param int $contact_id The contact ID to attach the number
	 * @param array $vars An array of number information including:
	 * 	- number The phone number to add
	 * 	- type The type of phone number 'phone', 'fax' (optional, default 'phone')
	 * 	- location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
	 * @return int The contact number ID of the number created, void if error
	 */
	public function addNumber($contact_id, array $vars) {
		$this->Input->setRules($this->getNumberRules());
		
		// Validate contact_id as well
		$vars['contact_id'] = $contact_id;
		
		if ($this->Input->validates($vars)) {
			// Add the number
			$fields = array("contact_id", "number", "type", "location");			
			$this->Record->insert("contact_numbers", $vars, $fields);
				
			return $this->Record->lastInsertId();
		}
	}

	/**
	 * Updates an existing number
	 *
	 * @param int $contact_number_id The number ID to update
	 * @param array $vars An array of number information including:
	 * 	- number The phone number to add
	 * 	- type The type of phone number 'phone', 'fax' (optional, default 'phone')
	 * 	- location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
	 */	
	public function editNumber($contact_number_id, array $vars) {
		$rules = $this->getNumberRules(false, true, true);
		
		// Remove contact_id constraint
		unset($rules['contact_id']);
		$this->Input->setRules($rules);
		
		// Validate the contact number ID as well
		$vars['id'] = $contact_number_id;
		
		if ($this->Input->validates($vars)) {
			// Update the number
			$fields = array("number", "type", "location");
			$this->Record->where("id", "=", $contact_number_id)->update("contact_numbers", $vars, $fields);
		}
	}
	
	/**
	 * Adds multiple numbers for a contact.
	 * If a contact number ID is provided and exists, the number will be updated or deleted if it is empty.
	 *
	 * @param int $contact_id The contact ID to attach numbers to
	 * @param array $vars A numerically indexed array of number information including:
	 *  - id The ID of a specific contact number to update (optional)
	 * 	- number The phone number to add
	 * 	- type The type of phone number 'phone', 'fax' (optional, default 'phone')
	 * 	- location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
	 *
	public function addNumbers($contact_id, array $vars) {
		$this->Input->setRules($this->getNumberRules(true, true, false));
		
		$numbers = array('numbers'=>$vars);
		
		if ($this->Input->validates($numbers)) {
			// Add/Update/Delete contact numbers
			foreach ($vars as $number) {
				// Ignore a case that neither ID nor number have been set
				if (empty($number['id']) && empty($number['number']))
					continue;
				
				// Delete the number if the number is empty
				if (empty($number['number'])) {
					$this->deleteNumber($number['id']);
					continue;
				}
				
				// Add a new number if ID is empty, otherwise update an existing one
				if (empty($number['id']))
					$this->addNumber($contact_id, $number);
				else
					$this->editNumber($number['id'], $number);
			}
		}
	}
	 */
	
	/**
	 * Permanently removes a number from the system
	 *
	 * @param int $contact_number_id The Id of the number to delete
	 */
	public function deleteNumber($contact_number_id) {
		// Delete the number
		$this->Record->from("contact_numbers")->where("id", "=", $contact_number_id)->delete();
	}
	
	/**
	 * Fetches a specific number
	 *
	 * @param int $contact_number_id The ID of the number to fetch
	 * @return mixed A stdClass object representing the number, false if no such number exists
	 */
	public function getNumber($contact_number_id) {
		$fields = array("id","contact_id","number","type","location");
		return $this->Record->select($fields)->from("contact_numbers")->
			where("id", "=", $contact_number_id)->fetch();
	}
	
	/**
	 * Fetches all numbers for the given contact and (optionally) type
	 *
	 * @param int $contact_id The contact ID to fetch all numbers for
	 * @param string $type The type of number to fetch ('phone', or 'fax', default null for all)
	 * @param string $location The location of the number to fetch ('home', 'work', or 'mobile', default null for all)
	 * @return mixed An array of stdClass objects representing contact numbers, false if no records found
	 */
	public function getNumbers($contact_id, $type=null, $location=null, $order=array("FIELD(location,'work','home','mobile')"=>"ASC")) {
		// Set whether we need to escape the order by clause
		$escape_orderby = (isset($order["FIELD(location,'work','home','mobile')"]) && (count($order) == 1) ? false : true);
		
		$fields = array("id","contact_id","number","type","location");
		$this->Record->select($fields)->from("contact_numbers")->
			where("contact_id", "=", $contact_id);
		if ($type !== null)
			$this->Record->where("type", "=", $type);
		if ($location !== null)
			$this->Record->where("location", "=", $location);
		return $this->Record->order($order, $escape_orderby)->fetchAll();
	}
	
	/**
	 * Returns a list of contact number types
	 *
	 * @return array A key=>value list of contact number types
	 */
	public function getNumberTypes() {
		return array(
			'phone'=>$this->_("Contacts.getnumbertypes.phone"),
			'fax'=>$this->_("Contacts.getnumbertypes.fax")
		);
	}
	
	/**
	 * Returns a list of contact number locations
	 *
	 * @return array A key=>value list of contact number locations
	 */
	public function getNumberLocations() {
		return array(
			'home'=>$this->_("Contacts.getnumberlocations.home"),
			'work'=>$this->_("Contacts.getnumberlocations.work"),
			'mobile'=>$this->_("Contacts.getnumberlocations.mobile")
		);
	}
	
	/**
	 * Internationalizes the given format number (+NNN.NNNNNNNNNN)
	 *
	 * @param string $number The phone number
	 * @param string $country The ISO 3166-1 alpha2 country code
	 * @param string $code_delimiter The delimiter to use between the country prefix and the number
	 * @return string The number in +NNN.NNNNNNNNNN
	 */
	public function intlNumber($number, $country, $code_delimiter = ".") {
		Configure::load("i18");
		$prefixes = Configure::get("i18.calling_codes");
		
		if (!isset($prefixes[$country]))
			return $number;

		$prefix = $prefixes[$country];

		// Is number already internationalized?
		if ($number != "" && $number[0] == "+") {
			$number = ltrim($number, "+");

			// Invalid format if prefix isn't in the number
			if (strpos($number, $prefix) !== 0)
				return $number;
			
			$number = substr_replace($number, "", 0, strlen($prefix));
		}

		$prefix = preg_replace("/[^0-9]+/", "", $prefix);
		$number = preg_replace("/[^0-9]+/", "", $number);
		
		return "+" . $prefix . $code_delimiter . $number;
	}
	
	/**
	 * Returns the rule set for adding/editing contacts
	 *
	 * @param array $vars The input vars
	 * @return array Contact rules
	 */
	private function getAddRules(array $vars) {
		$rules = array(
			'client_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "clients"),
					'message' => $this->_("Contacts.!error.client_id.exists")
				)
			),
			'user_id' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "users"),
					'message' => $this->_("Contacts.!error.user_id.exists")
				)
			),
			'contact_type' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateContactType"), $this->ifSet($vars['contact_id']), $this->ifSet($vars['client_id'])),
					'message' => $this->_("Contacts.!error.contact_type.format")
				)
			),
			'contact_type_id' => array(
				'format' => array(
					'rule' => array(array($this, "validateContactTypeId"), $this->ifSet($vars['contact_type']), $this->ifSet($vars['client_id'])),
					'message' => $this->_("Contacts.!error.contact_type_id.format")
				)
			),
			'first_name' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Contacts.!error.first_name.empty"),
					'post_format' => array("trim")
				)
			),
			'last_name' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Contacts.!error.last_name.empty"),
					'post_format' => array("trim")
				)
			),
			'title' => array(
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 64),
					'message' => $this->_("Contacts.!error.title.length")
				)
			),
			'company' => array(
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 64),
					'message' => $this->_("Contacts.!error.company.length")
				)
			),
			'email' => array(
				'format' => array(
					'rule' => "isEmail",
					'message' => $this->_("Contacts.!error.email.format"),
					'post_format' => array("trim")
				)
			),
			'state' => array(
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 3),
					'message' => $this->_("Contacts.!error.state.length")
				),
				'country_exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateStateCountry"), $this->ifSet($vars['country'])),
					'message' => $this->_("Contacts.!error.state.country_exists")
				)
			),
			'country' => array(
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 3),
					'message' => $this->_("Contacts.!error.country.length")
				)
			)
		);
		return $rules;
	}
	
	/**
	 * Returns the rule set for adding/editing numbers
	 *
	 * @param boolean $multiple True if validating multiple numbers, false to validate a single number (optional, default false)
	 * @param boolean $edit True if editing numbers, false otherwise (optional, default false)
	 * @param boolean $require_id True to require ID when editing numbers, false otherwise (optional, default false)
	 * @return array Number rules
	 */
	private function getNumberRules($multiple=false, $edit=false, $require_id=false) {			
		$rules = array(
			'contact_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "contacts"),
					'message' => $this->_("Contacts.!error.contact_id.exists")
				)
			),
			/*
			'number' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Contacts.!error.number.empty")
				)
			),
			*/
			'type' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateContactNumberType")),
					'message' => $this->_("Contacts.!error.type.format")
				)
			),
			'location' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateContactNumberLocation")),
					'message' => $this->_("Contacts.!error.location.format")
				)
			)
		);
		
		if ($edit) {
			// Check ID exists on edit
			if ($require_id) {
				$rules['id'] = array(
					'exists' => array(
						'rule' => array(array($this, "validateExists"), "id", "contact_numbers"),
						'message' => $this->_("Contacts.!error.id.exists")
					)
				);
			}
			else {
				// Remove number constraint for deleting purposes on edit. @see Contacts::edit
				//unset($rules['number']);
			}
		}
		
		if ($multiple) {
			unset($rules['contact_id']);
			
			// Format number rules to suit our other rules
			foreach ($rules as $key => $value) {
				$new_key = "numbers[][" . $key . "]";
				$rules[$new_key] = $value;
				unset($rules[$key]);
			}
		}
		
		return $rules;
	}
	
	/**
	 * Returns the rule set for adding/editing types
	 * 
	 * @return array Type rules
	 */
	private function getTypeRules() {
		$rules = array(
			'company_id' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "companies"),
					'message' => $this->_("Contacts.!error.company_id.format")
				)
			),
			'name' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Contacts.!error.name.empty")
				)
			),
			'is_lang' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "is_numeric",
					'message' => $this->_("Contacts.!error.is_lang.format")
				),
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 1),
					'message' => $this->_("Contacts.!error.is_lang.length")
				)
			)
		);
		return $rules;
	}
	
	/**
	 * Validates the contact numbers 'type' field
	 *
	 * @param string $type The type to check
	 * @return boolean True if validated, false otherwise
	 */
	public function validateContactNumberType($type) {
		return in_array($type, array("phone", "fax"));
	}
	
	/**
	 * Validates the contact numbers 'location' field
	 *
	 * @param string $location The location to check
	 * @return boolean True if validated, false otherwise
	 */
	public function validateContactNumberLocation($location) {
		return in_array($location, array("home", "work", "mobile"));
	}
	
	/**
	 * Validates a contact's 'contact_type' field
	 *
	 * @param string $contact_type The contact type to check
	 * @param int $contact_id The ID of the contact being updated
	 * @param int $client_id The ID of the contact being added to
	 * @return boolean True if validated, false otherwise
	 */
	public function validateContactType($contact_type, $contact_id=null, $client_id=null) {
		// Ensure that if a contact is the primary contact that it can not be changed.
		if ($contact_id !== null) {
			$contact = $this->Record->select(array("contacts.contact_type"))->from("contacts")->
				where("contacts.id", "=", $contact_id)->fetch();
			if ($contact && $contact->contact_type == "primary" && $contact->contact_type != $contact_type)
				return false;
		}
		// Ensure that only one contact may be set as 'primary' for each client
		if ($client_id !== null && $contact_type == "primary") {
			$contact = $this->Record->select(array("contacts.id"))->from("contacts")->
				where("contacts.contact_type", "=", "primary")->
				where("contacts.client_id", "=", $client_id)->fetch();
			if ($contact && $contact->id != $contact_id)
				return false;
		}
		
		return in_array($contact_type, array("primary", "billing", "other"));
	}
	
	/**
	 * Validates a contact's 'contact_type_id' field
	 *
	 * @param int $contact_type_id The contact type ID to check
	 * @param string $contact_type The contact type
	 * @param int $client_id The ID of the client the contact's type is being set for
	 * @return boolean True if validated, false otherwise
	 */
	public function validateContactTypeId($contact_type_id, $contact_type, $client_id) {
		// A valid contact type ID must be set if $contact_type == "other"
		// if it is not, return false
		if ($contact_type == "other") {
			// Verify that the contact type ID is available to this client
			$count = $this->Record->select(array("contact_types.id"))->from("contact_types")->
				innerJoin("client_groups", "client_groups.company_id", "=", "contact_types.company_id", false)->
				on("clients.id", "=", $client_id)->
				innerJoin("clients", "clients.client_group_id", "=", "client_groups.id", false)->
				where("contact_types.id", "=", $contact_type_id)->numResults();
			
			if ($count <= 0)
				return false;
		}
		return true;
	}
	
	/**
	 * Validates a country is set when a state is provided
	 *
	 * @param string $state The state
	 * @param string $country The country
	 * @return boolean True if a country is set, false otherwise
	 */
	public function validateCountrySet($state, $country) {
		return !empty($country);
	}
	
	/**
	 * Validates whether this contact is the primary contact for a client
	 *
	 * @param int $contact_id The contact ID
	 * @return boolean True if this contact is a client's primary billing contact, false otherwise
	 */
	public function validateIsPrimary($contact_id) {
		$contact = $this->get($contact_id);
		
		if ($contact && $contact->contact_type == "primary")
			return true;
		return false;
	}
	
	/**
	 * Validates whether the given contact is the contact whom invoices are addressed to and
	 * can be based on the given contact type
	 *
	 * @param string $contact_type The contact type of the contact (i.e. "primary", "billing", "other"; optional)
	 * @param int $contact_id The contact ID
	 * @return boolean True if this contact is the contact whom invoices are addressed to, false otherwise
	 */
	public function validateReceivesInvoices($contact_type=null, $contact_id=null) {
		$contact = $this->get($contact_id);
		
		if ($contact) {
			$count = $this->Record->select("key")->
				from("client_settings")->
				where("client_id", "=", $contact->client_id)->
				where("key", "=", "inv_address_to")->
				where("value", "=", $contact->id)->
				numResults();
			
			// The contact may only be the invoiced contact if it is a primary/billing contact
			// and is set to receive invoices
			if ($count > 0 && !in_array($contact_type, array("primary", "billing")))
				return true;
		}
		return false;
	}
}
?>