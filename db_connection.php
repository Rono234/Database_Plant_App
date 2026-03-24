<?php
 $con = mysqli_connect("localhost","root","root")or 
     die("Connection failed: " . mysqli_connect_error());
     mysqli_set_charset($con,"utf8mb4");
     $db = mysqli_select_db($con,"course_project") or
     die("Could not select database: " . mysqli_error($con));
?>