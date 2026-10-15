<?php

  $mysqli = new mysqli("localhost","2463090","2463090","db2463090");

  if ($mysqli -> connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
    exit();
  }
?>
