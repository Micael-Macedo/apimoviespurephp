<?php 
class JWT{
    public function generateToken(){
        session_start();
        session_regenerate_id();
        $token = session_id();
        return $token;
    }
}