<?php
include '../includes/connect.php';


function validRegNo($reg_num){

    if (!preg_match('/^[A-Z]|[a-z]{2}\d{3}\/\d{5,7}\/\d{2}$/',$reg_num)){

        return "Invalid Registration Number! format <XX***/******/**>";

    }
        #Breaks down the regno into segments eg.CT206 / 109610 / 22 
        $parts = explode('/', $reg_num);
        $unique_num =$parts[1];

        #validates the length of the unique identifier part
        if (strlen($unique_num) < 5 || strlen($unique_num) > 7){
            return "The unique number of the registration number is out of bound";
        }

        #validates the the last segment
        $year = (int)substr($reg_num, -2);
        if ($year < 16 || $year > 24){
            return "Year of admission is invalid or expired";
    }
    return null;
}


function is_strong_password($password) {

    return   preg_match('/[A-Z]/', $password) &&
             preg_match('/[a-z]/', $password) &&
             preg_match('/[0-9]/', $password) &&
             preg_match('/[\W_]/', $password) &&
             strlen($password) >= 8;
}


?>