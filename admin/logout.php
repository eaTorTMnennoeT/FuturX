<?php
/**
 * FuturX Admin - 로그아웃
 */

require_once 'auth.php';

adminLogout();

header('Location: login.php');
exit;
