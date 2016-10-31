<?php

namespace hypeJunction\GameMechanics;

/**
 * Check if the event qualifies for points and award them to the user
 *
 * @param string $event  Event type
 * @param string $type   'object'|'user'|'group'|'relationship'|'annotation'|'metadata'
 * @param mixed  $object Event object
 * @return boolean
 */
function apply_event_rules($event, $type, $object) {
	
	// Object
	if (is_object($object)) {
		$entity = $object;
	} else if (is_array($object)) {
		$entity = elgg_extract('entity', $object, null);
		if (!$entity) {
			$entity = elgg_extract('user', $object, null);
		}
		if (!$entity) {
			$entity = elgg_extract('group', $object, null);
		}
	}

	if (!is_object($entity)) {
		// Terminate early, nothing to act upon
		return true;
	}

	// Get rules associated with events
	$rules = get_scoring_rules('events');

	$event_name = "$event::$type";

	// Apply rules
	foreach ($rules as $rule_name => $rule_options) {

		if (!in_array($event_name, (array) $rule_options['events'])) {
			continue;
		}
		
		$rule_options['name'] = $rule_name;
		$gmRule = gmRule::applyRule($entity, $rule_options, $event_name);

		$errors = $gmRule->getErrors();
		if ($errors) {
			foreach ($errors as $error) {
				register_error($error);
			}
		}

		$messages = $gmRule->getMessages();
		if ($messages) {
			foreach ($messages as $message) {
				system_message($message);
			}
		}

		if ($gmRule->terminateEvent()) {
			return false;
		}
	}

	return true;
}

/**
 * Run upgrade scripts
 * @return void
 */
function upgrade() {

	if (!elgg_is_admin_logged_in()) {
		return;
	}

	include_once dirname(dirname(__FILE__)) . '/activate.php';
	
	$release = HYPEGAMEMECHANICS_RELEASE;
	$old_release = elgg_get_plugin_setting('release', 'hypeGameMechanics');

	if ($release > $old_release) {
		include_once dirname(dirname(__FILE__)) . '/lib/upgrade.php';
		elgg_set_plugin_setting('release', $release, 'hypeGameMechanics');
	}
	
}
