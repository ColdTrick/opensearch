import 'jquery';
import Ajax from 'elgg/Ajax';

$(document).on('submit', 'form.elgg-form-opensearch-admin-search', function(event) {
	event.preventDefault();
	
	var $form = $(this);
	var ajax = new Ajax();
	ajax.action('opensearch/admin_search', {
		data: ajax.objectify($form),
		success: function(data) {
			$('#opensearch-admin-search-results > .elgg-body').html(data);
		}
	});
});
