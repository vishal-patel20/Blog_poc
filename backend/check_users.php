<?php
$pdo = new PDO('sqlite:database/database.sqlite');
print_r($pdo->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC));
