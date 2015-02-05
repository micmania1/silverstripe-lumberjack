<?php

/**
 * Class Lumberjack
 *
 * Add this classes to SiteTree classes which children should be hidden.
 *
 * @package silverstripe
 * @subpackage lumberjack
 *
 * @author Michael Strong <mstrong@silverstripe.org>
 */
class Lumberjack extends Hierarchy {

	/**
	 * Loops through subclasses of the owner (intended to be SiteTree) and checks if they've been hidden.
	 *
	 * @return array
	 **/
	public function getExcludedSiteTreeClassNames() {
		$classes = array();
		$siteTreeClasses = $this->owner->allowedChildren();
		foreach($siteTreeClasses as $class) {
			if(Injector::inst()->create($class)->config()->show_in_sitetree === false) {
				$classes[$class] = $class;
			}
		}
		return $classes;
	}


	/**
	 * This is responsible for adding the child pages tab and gridfield.
	 *
	 * @param FieldList $fields
	 */
	public function updateCMSFields(FieldList $fields) {
		$excluded = $this->owner->getExcludedSiteTreeClassNames();
		if(!empty($excluded)) {
			$pages = SiteTree::get()->filter(array(
				'ParentID' => $this->owner->ID,
				'ClassName' => $excluded
			));
			$gridField = new GridField(
				"ChildPages",
				$this->getLumberjackTitle(),
				$pages,
				$this->getLumberjackGridFieldConfig()
			);

			$tab = new Tab('ChildPages', $this->getLumberjackTitle(), $gridField);
			$fields->insertAfter($tab, 'Main');
		}
	}


	/**
	 * Augments (@link Hierarchy::stageChildren()}
	 *
	 * @param $staged DataList
	 * @param $showAll boolean
	 **/
	public function stageChildren($showAll = false) {
		$staged = parent::stageChildren($showAll);

		if($this->shouldFilter()) {
			// Filter the SiteTree
			return $staged->exclude("ClassName", $this->owner->getExcludedSiteTreeClassNames());
		}
		return $staged;
	}


	/**
	 * Augments (@link Hierarchy::liveChildren()}
	 *
	 * @param $staged DataList
	 * @param $showAll boolean
	 **/
	public function liveChildren($showAll = false, $onlyDeletedFromStage = false) {
		$staged = parent::liveChildren($showAll, $onlyDeletedFromStage);

		if($this->shouldFilter()) {
			// Filter the SiteTree
			return $staged->exclude("ClassName", $this->owner->getExcludedSiteTreeClassNames());
		}
	}


	/**
	 * This returns the title for the tab and GridField. This can be overwritten
	 * in the owner class.
	 *
	 * @return string
	 */
	protected function getLumberjackTitle() {
		if(method_exists($this->owner, 'getLumberjackTitle')) {
			return $this->owner->getLumberjackTitle();
		}
		return _t("Lumberjack.TabTitle", "Child Pages");
	}


	/**
	 * This returns the gird field config for the lumberjack gridfield.
	 *
	 * @return GridFieldConfig
	 */
	protected function getLumberjackGridFieldConfig() {
		if(method_exists($this->owner, 'getLumberjackGridFieldConfig')) {
			return $this->owner->getLumberjackGridFieldConfig();
		}
		return GridFieldConfig_Lumberjack::create();
	}


	/**
	 * Checks if we're on a controller where we should filter. ie. Are we loading the SiteTree?
	 *
	 * @return bool
	 */
	protected function shouldFilter() {
		$controller = Controller::curr();
		return get_class($controller) == "CMSPagesController"
			&& in_array($controller->getAction(), array("treeview", "listview", "getsubtree"));
	}

}
