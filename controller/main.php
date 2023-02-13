<?php

class Main {

	//svn up 需要给目录777权限，或改为nginx 同一分组755

	function __construct() {

		if (empty($_SESSION['id'])) {
			include "_tpl/login.html";
			exit;
		}

		if ($_POST && !in_array($_POST['branch_id'], $_SESSION['svn_arr'])) {
			include "_tpl/login.html";
			exit;
		}
	}

	function main() {

		$svn_json = file_get_contents("./data/svn.json");

		$svn = json_decode($svn_json, 1);

		$svn_arr = [];

		foreach ($_SESSION['svn_arr'] as $key => &$value) {
			$svn_arr[$key]['id'] = $value;
			$svn_arr[$key]['branch_name'] = $svn[$value]['branch_name'];
		}

		include "_tpl/main.html";
	}

	//当前用户分支
	function branch() {

		if (in_array($_POST['branch_id'], $_SESSION['svn_arr'])) {

			exit(json_encode(['code' => 200, 'msg' => 'success']));
		} else {
			exit(json_encode(['code' => 100, 'msg' => 'fail']));
		}

	}

	//分支版本
	function branch_log() {

		$branch_id = $_POST['branch_id'];

		$svn_json = file_get_contents("./data/svn.json");

		$svn = json_decode($svn_json, 1);

		$new = $svn[$branch_id]['new'];
		$release = $svn[$branch_id]['release'];

		$status = $output = '';

		$username = $_SESSION['username'];
		$password = $_SESSION['password'];
		//先更新new
		exec("svn up   --username $username --password $password --no-auth-cache $new 2>&1");

		//最新log
		exec("export LANG=en_US.UTF-8  &&  svn log -l 5 --username $username --password $password --no-auth-cache $new 2>&1", $output, $status);

		exec("svn info $release 2>&1", $output_info, $status);

		//var_dump($output, $status, $output_info);exit;

		$now_version = str_replace('Revision:', '', $output_info['5']);
		$now_version = str_replace(' ', '', $now_version);

		$output_data = array_chunk($output, 4);

		$data = [];

		foreach ($output_data as $key => $value) {

			if (count($value) < 4) {continue;}
			$value[1] = str_replace(' ', '', $value[1]);

			$r = explode('|', $value[1]);

			$data[$key]['id'] = str_replace('r', '', $r[0]);
			$data[$key]['user'] = $r[1] == 'liyaoliang' ? "test" : $r[1];
			$data[$key]['time'] = $r[2];
			$data[$key]['desc'] = $value[3]; //substr($value[3], 0, 50);

		}

		exit(json_encode(['code' => 200, 'data' => ['list' => $data, 'now_version' => $now_version]]));

	}

	//合并
	function branch_merge() {

		$branch_id = $_POST['branch_id'];
		$version = $_POST['version'];

		$svn_json = file_get_contents("./data/svn.json");

		$svn = json_decode($svn_json, 1);

		$new = $svn[$branch_id]['new'];
		$release = $svn[$branch_id]['release'];

		$username = $_SESSION['username'];
		$password = $_SESSION['password'];

		//exec("chmod -R 777  $release 2>&1", $output_info, $a);
		exec("svn up -r $version --username $username --password $password --no-auth-cache $release 2>&1", $output, $status);
		//var_dump($output);
		if ($status === 0) {
			exit(json_encode(['code' => 200, 'msg' => implode('<br>', $output)]));
		}
		exit(json_encode(['code' => 100, 'msg' => '合并失败']));

	}

	//发布
	function release() {

		$branch_id = $_POST['branch_id'];

		$version = $_POST['version'];

		$svn_json = file_get_contents("./data/svn.json");

		$svn = json_decode($svn_json, 1);

		$now_branch = $svn[$branch_id];

		$new = $now_branch['new'];
		$release = $now_branch['release'];

		$username = $_SESSION['username'];
		$password = $_SESSION['password'];

		$remote = $now_branch['remote'];

		foreach ($remote as $val) {

			$ip = $val['ip'];

			//此客户端password文件 不用用户名
			$password = ROOT . '/data/password/' . $ip . '.password';

			//$password = '/home/222.password';

			//将password 文件 改为nginx 同一分组，权限chmod -R 600

			$cmd = " rsync -arzvlLpogDtz --progress --password-file=$password $release root@{$ip}::{$now_branch['module_name']} 2>&1 ";

			exec($cmd, $output, $status);

			if ($status === 0) {

				exit(json_encode(['code' => 200, 'msg' => implode('<br>', $output)]));

			}
			exit(json_encode(['code' => 100, 'msg' => '发布失败']));

		}

	}

	//回滚
	function callback() {
		$branch_id = $_POST['branch_id'];
		$version = $_POST['version'];

		$svn_json = file_get_contents("./data/svn.json");

		$svn = json_decode($svn_json, 1);

		$new = $svn[$branch_id]['new'];
		$release = $svn[$branch_id]['release'];

		$username = $_SESSION['username'];
		$password = $_SESSION['password'];

		//exec("chmod -R 777  $release 2>&1", $output_info, $a);
		exec("svn up -r $version --username $username --password $password --no-auth-cache $release 2>&1", $output, $status);

		if ($status === 0) {
			exit(json_encode(['code' => 200, 'msg' => implode('<br>', $output)]));
		}
		exit(json_encode(['code' => 100, 'msg' => '回滚失败']));

	}
}
