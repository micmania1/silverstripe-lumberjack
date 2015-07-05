<?php

/**
 * Provides a component to the {@link GridField} which shows the publish status of a page.
 *
 * @package silverstripe
 * @subpackage lumberjack
 *
 * @author Michael Strong <mstrong@silverstripe.org>
**/
class GridFieldSiteTreeState implements GridField_ColumnProvider {

	public function augmentColumns($gridField, &$columns) {
        // Ensure Actions always appears as the last column.
        $key = array_search("Actions", $columns);
        if($key !== FALSE) unset($columns[$key]);

		$columns = array_merge($columns, array(
			"State",
			"Actions",
		));
	}

	public function getColumnsHandled($gridField) {
		return array("State");
	}

	public function getColumnContent($gridField, $record, $columnName) {
		if($columnName == "State") {
			if($record->hasMethod("isPublished")) {
				$modifiedLabel = "";
				if($record->isModifiedOnStage) {
					$modifiedLabel = "<span class='modified'>" . _t("GridFieldSiteTreeState.Modified") . "</span>";
				}

				if($record->IsDeletedFromStage) {
					if($record->ExistsOnLive) {
						return _t('SiteTree.REMOVEDFROMDRAFTHELP', 'Page is published, but has been deleted from draft');
					} else {
						return _t('SiteTree.DELETEDPAGEHELP', 'Page is no longer published');
					}
				} else if($record->IsAddedToStage) {
					return _t('SiteTree.ADDEDTODRAFTHELP', "Page has not been published yet");
				} else if($record->IsModifiedOnStage) {
					return _t('SiteTree.MODIFIEDONDRAFTHELP', 'Page has unpublished changes');
				} else {
					return _t(
						"GridFieldSiteTreeState.Published",
						'<i class="btn-icon gridfield-icon btn-icon-accept"></i> Published on {date}',
						"State for when a post is published.",
						array(
							"date" => $record->dbObject("LastEdited")->Nice()
						)
					) . $modifiedLabel;
				}
			}
		}
	}

	public function getColumnAttributes($gridField, $record, $columnName) {
		if($columnName == "State") {
			if($record->hasMethod("isPublished")) {
				$published = $record->isPublished();
				if(!$published) {
					$class = "gridfield-icon draft";
				} else {
					$class = "gridfield-icon published";
				}
				return array("class" => $class);
			}
		}
		return array();
	}

	public function getColumnMetaData($gridField, $columnName) {
		switch($columnName) {
			case 'State':
				return array("title" => _t("GridFieldSiteTreeState.StateTitle", "State", "Column title for state"));
		}
	}

}
