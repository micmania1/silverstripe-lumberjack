<?php

/**
 * This component creates a dropdown of possible page types and a button to create a new page.
 *
 * This bypasses GridFieldDetailForm to use the standard CMS.
 *
 * @package silverstripe
 * @subpackage lumberjack
 *
 * @author Michael Strong <mstrong@silverstripe.org>
 */
class GridFieldSiteTreeAddNewButton extends GridFieldAddNewButton
	implements GridField_ActionProvider {


	public function getAllowedChildren(SiteTree $parent) {
		$allowedChildren = $parent->allowedChildren();
		if(empty($allowedChildren)) return array();

		$children = array();
		foreach($allowedChildren as $class) {
			if(Config::inst()->get($class, "show_in_sitetree") === false) {
				$children[$class] = Injector::inst()->create($class)->i18n_singular_name();
			}
		}
		return $children;
	}

	public function getHTMLFragments($gridField) {
		$model = Injector::inst()->create($gridField->getModelClass());
		$parent = SiteTree::get()->byId(Controller::curr()->currentPageID());

		if(!$model->canCreate()) {
			return array();
		}

		$children = $this->getAllowedChildren($parent);
		if(count($children) > 1) {
			$pageTypes = DropdownField::create("PageType", "Page Type", $children, $model->defaultChild());
			$pageTypes->setFieldHolderTemplate("GridFieldSiteTreeAddNewButton_holder")->addExtraClass("gridfield-dropdown no-change-track");

			if(!$this->buttonName) {
				$this->buttonName = _t('GridFieldSiteTreeAddNewButton.AddMultipleOptions', 'Add new', "Add button text for multiple options.");
			}
		} else {
			$keys = array_keys($children);
			$pageTypes = HiddenField::create('PageType', 'Page Type', $keys[0]);

			if(!$this->buttonName) {
				$this->buttonName = _t('GridFieldSiteTreeAddNewButton.Add', 'Add new {name}', 'Add button text for a single option.', array($children[$keys[0]]));
			}
		}

		$state = $gridField->State->GridFieldSiteTreeAddNewButton;
		$state->currentPageID = $parent->ID;
		$state->pageType = $parent->defaultChild();

		$addAction = new GridField_FormAction($gridField, 'add', $this->buttonName, 'add', 'add');
		$addAction->setAttribute('data-icon', 'add')->addExtraClass("no-ajax ss-ui-action-constructive dropdown-action");

		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList();
		$forTemplate->Fields->push($pageTypes);
		$forTemplate->Fields->push($addAction);

		Requirements::css(LUMBERJACK_DIR . "/css/lumberjack.css");
		Requirements::javascript(LUMBERJACK_DIR . "/javascript/GridField.js");

		return array($this->targetFragment => $forTemplate->renderWith("GridFieldSiteTreeAddNewButton"));
	}



	/**
	 * Provide actions to this component.
	 *
	 * @param $gridField GridField
	 *
	 * @return array
	**/
	public function getActions($gridField) {
		return array("add");
	}



	/**
	 * Handles the add action, but only acts as a wrapper for {@link CMSPageAddController::doAdd()}
	 *
	 * @param $gridFIeld GridFIeld
	 * @param $actionName string
	 * @param $arguments mixed
	 * @param $data array
	**/
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {

		if($actionName == "add") {
			$tmpData = json_decode($data['ChildPages']['GridState'], true);
			$tmpData = $tmpData['GridFieldSiteTreeAddNewButton'];
			
			$data = array(
				"ParentID" => $tmpData['currentPageID'],
				"PageType" => $tmpData['pageType']
			);

			$controller = Injector::inst()->create("CMSPageAddController");

			$form = $controller->AddForm();
			$form->loadDataFrom($data);

			$controller->doAdd($data, $form);
			$response = $controller->getResponseNegotiator()->getResponse();

			// Get the current record
			$record = SiteTree::get()->byId($controller->currentPageID());
			if($record) {
				$response->redirect(Director::absoluteBaseURL() . $record->CMSEditLink(), 301);
			}
			return $response;

		}
	}

}
