<?php

//set headers for api response
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Route all requests through routes/web.php
require_once './routes/api.php';
