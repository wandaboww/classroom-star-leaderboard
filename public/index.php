<?php
/**
 * Index — redirect ke login atau dashboard
 */
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (Auth::check()) {
    Auth::redirectToDashboard();
} else {
    header('Location: login.php');
    exit;
}
