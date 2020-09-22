<?php

declare(strict_types = 1);


/** @var MySQLi $mysqli */

// Unsafe method
$sql = 'select * from bookmarks b';
$sql .= 'where b.user_id = ' . $_GET['id'];
$mysqli->query($sql);

// Safe method
$sql = new SafeMysqliQueryBuilder('select * from bookmarks b');
$sql->append('where b.user_id = :user_id');
$sql->setParameter('user_id', $_GET['id']);
$mysqli->query($sql);