<?php
  try {
    $bdd = new PDO("$db_type:host=$db_host;dbname=$db_name;charset=utf8", $db_username, $db_password);
  } catch (\Exception $e) {
     die('Erreur : ' . $e->getMessage());
  }
?>
