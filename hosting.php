<?php
/*
 * itflow.org - the sign-up page's old address. The sign-up is signup.php now
 * (hosted ITFlow and sponsorship both), so links here - with their ?want=,
 * ?tier= and ?seats= - are sent on to it.
 */

// A form opened here before the move still sends here: take it as signup.php would
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/signup.php';
    exit;
}

$query = (string) ($_SERVER['QUERY_STRING'] ?? '');
header('Location: signup.php' . ($query !== '' ? '?' . $query : ''), true, 301);
exit;
