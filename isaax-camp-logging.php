<?php
/*
* Plugin Name: Isaax Campのロギング用プラグイン
* Version: 1
* Author: Xshell,Inc.
* Text Domain: isaax-camp-logging
* Author URI: https://xshell.io
* License: GPL2
*/

require __DIR__.'/vendor/autoload.php';
/**
 * wp-configに以下の記述を追加してください
 define('PATH_TO_FIREBASE_CREDENTIALS', '/path/to/client_credentials.json');
 */

/**
 * GAにUser-IDを送信する
 */
add_action( 'wp_footer', function () {
	$user = wp_get_current_user();
	$firebaseScript = <<<SCRIPT
<!-- The core Firebase JS SDK is always required and must be listed first -->
<script src="https://www.gstatic.com/firebasejs/7.8.1/firebase-app.js"></script>

<!-- TODO: Add SDKs for Firebase products that you want to use
		https://firebase.google.com/docs/web/setup#available-libraries -->
<script src="https://www.gstatic.com/firebasejs/7.8.1/firebase-analytics.js"></script>

<script>
	// Your web app's Firebase configuration
	var firebaseConfig = {
	apiKey: "AIzaSyBdp8eJ5n40jkEW045BvzVekj6Uu8LCUHg",
	authDomain: "honki-iot-users-access.firebaseapp.com",
	databaseURL: "https://honki-iot-users-access.firebaseio.com",
	projectId: "honki-iot-users-access",
	storageBucket: "honki-iot-users-access.appspot.com",
	messagingSenderId: "824806803009",
	appId: "1:824806803009:web:2019f5d9196d90279f68c0",
	measurementId: "G-CNK0XQE6RP"
	};
	// Initialize Firebase
	firebase.initializeApp(firebaseConfig);
	firebase.analytics().setUserProperties({
		user_id: %d,
		user_role: '%s',
		user_registered: '%s'
	});
</script>
SCRIPT;
	echo sprintf($firebaseScript,
		$user->ID, $user->roles[0], $user->user_registered
	);
});

/**
 * ユーザーログをFirebaseに残す
 */
function isaaxLogging() {
	return null;
	if ( !defined('PATH_TO_FIREBASE_CREDENTIALS') ) {
		return null;
	}
	if ( is_admin() ) {
		return null;
	}
	$user = wp_get_current_user();
	try {
		$firebase = (new \Kreait\Firebase\Factory())
				->withServiceAccount(PATH_TO_FIREBASE_CREDENTIALS);
		$db = $firebase->createDatabase();
		// Insert or update a dataset
		$r = $db->getReference(sprintf('views/%s',date('c')))
				->set([
					'user_id' => $user->ID,
					'role' => $user->roles,
					'permalink' => $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
					'user_agent' => $_SERVER['HTTP_USER_AGENT'],
					'http_referer' => $_SERVER['HTTP_REFERER'],
					'created' => date('c'),
		   ]);
	} catch (Exception $e) {
		var_dump($e->message);
	}
}
add_action( 'wp_loaded', 'isaaxLogging' );
