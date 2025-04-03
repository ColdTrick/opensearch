<?php

echo elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('opensearch:inspect:guid'),
	'#help' => elgg_echo('opensearch:inspect:guid:help'),
	'name' => 'guid',
	'value' => get_input('guid'),
	'required' => true,
	'min' => 1,
]);

// build footer
$footer = elgg_view_field([
	'#type' => 'submit',
	'icon' => 'search',
	'text' => elgg_echo('opensearch:inspect:submit'),
]);

elgg_set_form_footer($footer);
