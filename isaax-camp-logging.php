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
 * ログイン時のリダイレクト先をトップページへ
 */
add_action( 'admin_init', function () {
	if( !current_user_can('edit_posts') ){
		$home_url = site_url('', 'http');
		wp_safe_redirect($home_url);
		exit();
	}
} );

/**
 * ログアウト時のリダイレクト先をトップページへ
 */
add_action( 'wp_logout', function () {
	$home_url = site_url('', 'http');
	wp_safe_redirect($home_url);
	exit();
});

/**
 * 購読者がログイン時に管理バーを表示させない
 */
add_filter( 'show_admin_bar', function ($content) {
	if( current_user_can("edit_posts") ){
		return $content;
	}
	return false;
});

/**
 * GAにUser-IDを送信する
 */
add_action( 'wp_footer', function () {
	$user = wp_get_current_user();
	if( in_array('subscriber', $user->roles)){
		// ログインしている user_id を使用してUser-ID を設定します。
		$script = "<script>gtag('set', {'user_id': %s});</script>";
		echo sprintf($script, $user->user_login);
	}
});

/**
 * ユーザーログをFirebaseに残す
 */
function isaaxLogging() {
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
