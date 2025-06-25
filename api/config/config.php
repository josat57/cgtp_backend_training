<?php
class DBConfig
{
    protected function dbSettings() {
        $settings = [
            "db_host" => "localhost",
            "db_name" => "dbmeetme",
            "db_user" => "root",
            "db_password" => "root"
        ];
        return (object) $settings;
    }
    
    public function connectToDatabase() {
        $settings = $this->dbSettings();
        $conn = new mysqli($settings->db_host, $settings->db_user, $settings->db_password, $settings->db_name);
    
        if ($conn->connect_error) {
            return "Connection failed: " . $conn->connect_error;
        }
        return $conn;
    }
    
    public function closeDatabaseConnection($conn) {
        $conn->close();
    }
}
