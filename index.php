<?php
include_once('db.php');
$url = $_SERVER['REQUEST_URI'];
$fragUrl  = explode('/', $url);
$body = json_decode(file_get_contents('php://input'));
$header = json_encode(getallheaders());

$filtro = ['offset' => 0, 'limit' => 10, 'sortDir' => 'desc'];
$filtro = (object) $filtro;
if(isset($body->pageSize)){
    $filtro->limit = $body->pageSize;
}else{
    if($fragUrl[3] == 'movies'){
        $filtro->limit = 4;
    }
}
if(isset($body->page)){
    $filtro->offset = $filtro->limit * ($body->page - 1);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if(count($fragUrl) == 4){
            echo json_encode(getAllItems($fragUrl[3], $filtro));
        }else{
            echo json_encode(getItem($fragUrl[3], $filtro, $fragUrl[4]));
        }
        break;
    
    case 'POST':
        if($url == '/api/v1/auth/signup'){
            $user = ['password' => $body->password, 'email' => $body->email, 'name' => $body->name, 'username' => $body->username];
            $result = createUser((object) $user);
            setcookie('user_id',  $result->user_id);
            echo json_encode(['token' => $result->token ]);
        }
        if($url == '/api/v1/signin'){
            $user = ['password' => $body->password, 'email' => $body->email];
            $result = postLogin((object) $user);
            setcookie('user_id',  $result->user_id);
            echo json_encode(['token' => $result->token ]);
        }
        if($fragUrl[3] == 'reviews' && $fragUrl[4] != 'evaluations'){
            $review = ['content' => $body->content, 'stars' => $body->stars];
            postReview($user, $fragUrl[4], $review);
        }
        break;
        
        case 'DELETE':
            # code...
            if($url == '/api/v1/auth/signout'){

            }
        break;
    
    case 'PUT':
        # code...
        break;
    
    default:
        # code...
        break;
}

