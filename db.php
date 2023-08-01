<?php
include_once('jwt.php');
$jwt = new JWT();
function getSql($sql)
{
    $db = new mysqli('localhost', 'root', '1234', 'apimovies', 3308);
    $result = $db->query($sql);
    $array = [];
    if (is_bool($result)) {
        return $result;
    } else {
        while ($item = $result->fetch_object()) {
            array_push($array, $item);
        }
        if (count($array) == 1) {
            return $array[0];
        } else {
            return $array;
        }
    }
}

function getAllItems($object, $filter)
{
    $sql = "SELECT * FROM $object order by ($filter->sortBy) $filter->sortDir limit $filter->limit offset $filter->offset";
    return getSql($sql);
}
function getItem($object, $filter, $id)
{
    $sql = "SELECT * FROM $object where $object" . ".id = $id";
    if ($object == 'movies') {
        $movie = getSql($sql);
        $sql = "SELECT * FROM credit left join artists on artists.id = credit.artist_id where movie_id = $id ";
        $artists = getSql($sql);
        $movie->credits = $artists;
        return $movie;
    }
    if ($object == 'artists') {
        $artist = getSql($sql);
        $sql = "SELECT * FROM credit left join movies on movies.id = credit.movie_id where artist_id = $id ";
        $movies = getSql($sql);
        $artist->movies = $movies;
        return $artist;
    }
    if ($object == 'reviews') {
        $sql = "SELECT * FROM $object where movie_id = $id limit $filter->limit offset $filter->offset";
        return getSql($sql);
    }
    return getSql($sql);
}

function getToken($token)
{
    $sql = "SELECT * FROM accessTokens where stringToken = $token";
    return getSql($sql);
}

function logout($token)
{
    $dbToken = getToken($token);
    $sql = "DELETE accessToken where id = $dbToken->id";
    setcookie('user_id', null);
    return getSql($sql);
}
function createUser($user)
{
    $user->password = crypt($user->password, "SENHA");
    $sql = "INSERT INTO users(email, username, password, name) values('$user->email', '$user->username', '$user->password', '$user->name')";
    if (getSql($sql)) {
        global $jwt;
        $sql = "SELECT * FROM users where email = '$user->email' and password = '$user->password'";
        $userDb = getSql($sql);
        $token = $jwt->generateToken();
        if (isset($userDb->id)) {
            createToken($userDb->id, $token);
            setcookie('user_id', $userDb->id);
            $data = ['token' => $token, 'user_id' => $userDb->id];
            return (object) $data;
        }
    }
}
function postLogin($user)
{
    $user->password = crypt($user->password, "SENHA");
    $sql = "SELECT * FROM users where email = '$user->email' and password = '$user->password'";
    $userDb = getSql($sql);
    if (!is_bool($userDb)) {
        global $jwt;
        $token = $jwt->generateToken();
        session_regenerate_id();
        updateToken($userDb->id, $token);
        $data = ['token' => $token, 'user_id' => $userDb->id];
        setcookie('user_id', $userDb->id);
        return (object) $data;
    }
}
function validarToken($token)
{
    $sql = "SELECT * FROM accesstoken where tokenString = $token";
    $tokenDb = getSql($sql);
    if ($tokenDb != false) {
        return true;
    } else {
        return false;
    }

}
function updateToken($user, $token)
{
    $sql = "SELECT * FROM accesstoken where user_id = $user";
    $tokenDb = getSql($sql);
    $sql = "UPDATE accesstoken SET user_id = $user, tokenString = '$token' where id = $tokenDb->id";
    return getSql($sql);
}
function createToken($user, $token)
{
    $sql = "INSERT INTO accesstoken(user_id, tokenString) values ($user, '$token')";
    return getSql($sql);
}
function postReview($user, $movieId, $review)
{
    $sql = "SELECT * FROM reviews WHERE user_id = $user and movie_id = $movieId";
    $reviewDb = getSql($sql);
    if ($reviewDb != false) {
        $sql = "UPDATE reviews SET content = '$review->content', stars = '$review->stars' where review_id = $reviewDb->id";
        sendMessage('Review has been successfully updated');
    } else {
        $sql = "INSERT INTO reviews(movie_id, user_id, content, stars) values('$movieId', '$user', '$review->content', '$review->stars')";
        sendMessage('Review has been successfully created');
    }
    return getSql($sql);
}
function postEvaluation($user_id, $review_id, $positive)
{

    $sql = "SELECT * FROM reviewsEvaluation WHERE user_id = $user_id and review_id = $review_id";
    $evaluationDb = getSql($sql);
    var_dump($evaluationDb);
    if ($evaluationDb !== false) {
        if ($evaluationDb->user_id == $user_id) {
            $sql = false;
            sendMessage("The user cant evaluate his own review");
        } else {
            $sql = "UPDATE reviewsEvaluation SET positive = $positive WHERE id = $evaluationDb->id";
            sendMessage("Review evaluation has been successfully updated");
        }
    } else {
        $sql = "INSERT INTO reviewsEvaluation(review_id, user_id, positive) values('$user_id', '$review_id', '$positive')";
        sendMessage("Review evaluation has been successfully created");
    }
    if ($sql != false) {
        getSql($sql);
    }
}
function deleteEvaluation($review_id, $user_id)
{
    $sql = "SELECT * FROM reviewsEvaluation WHERE user_id = $user_id and review_id = $review_id";
    $evaluationDb = getSql($sql);
    if ($evaluationDb != false) {
        $sql = "DELETE reviewsEvaluation WHERE id = $evaluationDb->id";
    } else {
        sendMessage("You haven't published a evaluation to this review");
    }
}

function deleteReview($movie_id, $user_id)
{
    $sql = "SELECT * FROM reviews WHERE user_id = $user_id and movie_id = $movie_id";
    $reviewDB = getSql($sql);
    if ($reviewDB != false) {
        $sql = "DELETE reviews WHERE id = $reviewDB->id";
    } else {
        sendMessage("You haven't published a evaluation to this review");
    }
}