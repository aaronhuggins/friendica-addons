<?php
/**
 * Name: Emoji Picker
 * Description: Adds a native emoji picker to the Inputbox
 * Version: 1.0
 * Author: Aaron Huggins <https://friends.desk-apps.com/profile/aaronhuggins>
 * Maintainer: Aaron Huggins <https://friends.desk-apps.com/profile/aaronhuggins>
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\DI;

function emoji_picker_install() {
	//Register hooks
	Hook::register('footer', 'addon/emoji_picker/emoji_picker.php', 'emoji_picker_footer');
	Hook::register('jot_tool', 'addon/emoji_picker/emoji_picker.php', 'emoji_picker_jot_tool');
}

function is_supported(App $a) {
	// Disable for mobile because they have a smiley key of their own
	// Disable for any theme not frio, as other themes are not yet supported.
	return $a->getCurrentTheme() == 'frio' && DI::mode()->isMobile() == false;
}

function emoji_picker_footer(App $a, string &$body) {
	if (is_supported($a)) {
		return;
	}

	$script_file = DI::baseUrl()->get() . '/addon/emoji_picker/emoji_picker.js';
	//Add the hmtl and scripts to the page
	$body = <<< EOT
	<script src="https://cdn.jsdelivr.net/npm/picmo@5.7.2/dist/umd/index.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/@picmo/popup-picker@5.7.2/dist/umd/index.js"></script>
	<script src="$script_file"></script>
EOT;
}

function emoji_picker_jot_tool(App $a, string &$body) {
	if (is_supported($a)) {
		return;
	}

	$uri_parts = explode('?', $_SERVER['REQUEST_URI']);
	$path = $uri_parts[0];
	$button_id = "profile-emoji_picker-button";

	if (str_ends_with($path, '/compose')) {
		$body = ($body ?? '') . <<< EOT
	<div id="profile-emoji_picker-wrapper">
		<button type="button" class="btn" id="$button_id">
			<i class="fa fa-smile-o fa-emoji_picker" aria-hidden="true"></i>
		</button>
		<style>
			.fa-emoji_picker {
				font-weight: bold;
				font-size: x-large;
			}
		</style>
	</div>
EOT;
	} else {
		$body = ($body ?? '') . <<< EOT
	<button type="button" class="btn-link" id="$button_id">
		<i class="fa fa-smile-o fa-emoji_picker" aria-hidden="true"></i>
	</button>
	<style>
		.emoji_picker {
			z-index: 10000
		}
		.fa-emoji_picker {
			font-weight: bold;
		}
	</style>
EOT;
	}
}
