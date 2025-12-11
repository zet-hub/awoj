<?php
require_once __DIR__ . '/../helpers.php';
start_session();
session_destroy();
respond_json(['redirect' => 'login.html']);

