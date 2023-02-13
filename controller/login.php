<?php

class Login {

	function __construct() {
		if ($_SESSION['id'] && empty($_POST)) {

			header('location:/main/main');

			exit;
		}

	}

	function login() {

		include "_tpl/login.html";
	}

	function onlogin() {

		//身份校验
		$user_json = file_get_contents("./data/user.json");

		$user = json_decode($user_json, 1);

		foreach ($user as $key => $value) {

			if ($key == $_POST['username'] && $value['password'] == $_POST['password']) {

				$_SESSION['svn_arr'] = explode(',', $value['svn_arr']);
				$_SESSION['id'] = $key;
				$_SESSION['username'] = $key;
				$_SESSION['password'] = $value['password'];

				header('location:/main/main');
			}
		}

		$data = '用户名或密码错误！';
		include "_tpl/login.html";
	}

	function logout() {

		$_SESSION = [];
		session_destroy(); //清除服务器的sesion文件
		exit(json_encode(['code' => 200, 'msg' => 'success']));
	}

}
