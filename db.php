<?php
include_once('jwt.php');
$jwt = new JWT();
function getSql($sql){
    $db = new mysqli('localhost', 'root', '1234', 'apimovies', 3306);
    $result = $db->query($sql);
    $array = [];
    if(is_bool($result)){
        return $result;
    }else{
        while($item = $result->fetch_object()){
            array_push($array, $item);
        }
        if(count($array) == 1){
            return $array[0];
        }else{
            return $array;
        }
    }
}

function getAllItems($object, $filter){
    $sql = "SELECT * FROM $object";
    if($object == 'movies'){
        return getSql($sql);
    }
}
function getItem($object, $filter, $id){
    $sql = "SELECT * FROM $object where $object". ".id = $id";
    if($object == 'movies'){
        $movie = getSql($sql);
        $sql = "SELECT * FROM credit left join artists on artists.id = credit.artist_id where movie_id = $id";
        $artists = getSql($sql);
        $movie->credits = $artists;
        return $movie;
    }
    if($object == 'artists'){
        $sql = $sql . " INNER JOIN credit ON credit.artist_id = artists.id INNER JOIN movies on movies.id = credit.movie_id where artists.id = $id";
        return getSql($sql);
    }
    if($object == 'reviews'){
        $sql = $sql . " INNER JOIN movies ON reviews.movie_id = movies.id offset(".$filter->offset . ")";
        return getSql($sql);
    }
}

function getToken($token){
    $sql = "SELECT * FROM acessTokens where stringToken = $token";
    return getSql($sql);
}

function logout($token){
    $dbToken = getToken($token);
    $sql = "DELETE acessToken where id = $dbToken->id";
    return getSql($sql);
}
function createUser($user){
    $user->password = crypt($user->password, "SENHA");
    $sql = "INSERT INTO users(email, username, password, name) values('$user->email', '$user->username', '$user->password', '$user->name')";
    if(getSql($sql)){
        global $jwt;
        $sql = "SELECT * FROM users where email = '$user->email' and password = '$user->password'";
        $userDb = json_decode(getSql($sql));
        $token = $jwt->generateToken();
        if(isset($userDb->id)){

            createToken($userDb->id, $token);
            $data = ['token' => $token, 'user_id' => $userDb->id];
            return (object) $data;
        }
    }
}
function postLogin($user){
    $user->password = crypt($user->password, "SENHA");
    $sql = "SELECT * FROM users where email = '$user->email' and password = '$user->password'";
    $userDb = json_decode(getSql($sql));
    if(!is_bool($userDb)){
        global $jwt;
        $token = $jwt->generateToken();
        session_regenerate_id();
        updateToken($userDb->id, $token);
        $data = ['token' => $token, 'user_id' => $userDb->id];
        return (object) $data;
    }
}
function updateToken($user, $token){
    $sql = "SELECT * FROM accesstoken where user_id = $user";
    $tokenDb = json_decode(getSql($sql));
    $sql = "UPDATE accesstoken SET user_id = $user, tokenString = '$token' where id = $tokenDb->id";
    var_dump($sql);
    return getSql($sql);
}
function createToken($user, $token){
    $sql = "INSERT INTO accesstoken(user_id, tokenString) values ($user, '$token')";
    var_dump($sql);
    return getSql($sql);
}
function postCadastro($user, $review){}
function postReview($user, $movieId, $review){
    $sql = "INSERT INTO reviews(movie_id, user_id, content, stars) values('$movieId', '$user->id', '$review->content', '$review->stars')";
    return getSql($sql);
}
function updateReview($user,$movieId, $review){
    $sql = "UPDATE reviews SET movie_id = $movieId, user_id = $user->id, content = '$review->content', stars = '$review->stars')";
    return getSql($sql);
}
function postEvaluation($user, $review){}