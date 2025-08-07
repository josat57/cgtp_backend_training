<?php
// /models/model.php

require_once __DIR__ . '/../config/config.php';

class BaseModel {
    protected $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    // Optional: Add shared model functions here in the future
    // Example: fetchAll, findById, etc.
}
