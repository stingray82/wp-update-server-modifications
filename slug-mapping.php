<?php
header('Content-Type: application/json');

// Define the slug mapping array
$slug_mapping = array(
    "plugin-textdomain-xxx" => "plugin-server-slug-yyy",
    "theme-folder-aaa"      => "theme-server-slug-bbb"
);

// Output the array as JSON
echo json_encode($slug_mapping, JSON_PRETTY_PRINT);
