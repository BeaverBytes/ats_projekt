<?php
/**
 * public/logout.php 
 * HTTP-Entry-Point zum Beender einer Nutzersession
 * Delegiert Logout-Logik an src/auth.php Modul
 */

declare(strict_types=1);

// Einbingung des Auth-Moduls
require_once __DIR__ . '/../src/auth.php';

// Funktion zum leeren der Session-Daten, Invalidierung von Session-Cookies und serverseitige Beendung der Session
logout();

// Weiterleitung zur Login-Seite
header('Location: /ats_projekt/public/login.php');
exit;