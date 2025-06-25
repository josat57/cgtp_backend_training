<?php

class User {
    private $userId;
    public $firstName;
    public $email;

    public function getUserData($userId) {
        echo "My name is {$this->firstName} and my email is {$this->email}.";
    }
}

// $student = new User();
// $student->userId = 1;
// $student->firstName = "John";
// $student->email = "myemail@gmail.com";
// $student->getUserData($student->userId);

class dept extends User {
    public $deptId;
    public $deptName;

    public function getDeptData($deptId) {
        echo "This is: {$this->deptName}, ";
        $this->userId = 1;
        $this->firstName = "John";
        $this->email = "johnme@gmail.com";
        $this->getUserData($this->userId);
        echo "from the department of {$this->deptName}.";
    }
}

$department = new dept();
$department->deptId = 1;
$department->deptName = "Computer Science";
$department->getDeptData($department->deptId);
// Output: This is: Computer Science, My name is John and my email is