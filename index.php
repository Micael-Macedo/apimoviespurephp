<?php
include_once('db.php');
$url = $_SERVER['REQUEST_URI'];
$fragUrl  = explode('/', $url);
$body = json_decode(file_get_contents('php://input'));
$header =  (object) getallheaders();
function validar($header){
    if(isset($header->Authorization)){
        if(validarToken($header->Authorization)){
            return true;
        }else{
            sendMessage('Invalid token');
            http_response_code(403);
            return false;
        }
    }else{
        sendMessage('Unauthenticated user');
        http_response_code(401);
        return false;
    }
}

$filtro = ['offset' => 0, 'limit' => 10, 'sortDir' => 'desc', 'sortBy' => 'null'];
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
if(isset($body->sortDir)){
    $filtro->sortDir = $body->sortDir;
}else{
    if($fragUrl[3] == 'artists'){
        $filtro->sortDir == 'asc';
    }
}
if(isset($body->sortBy)){
    $filtro->sortBy = $body->sortBy;
}else{
    if($fragUrl[3] == 'artists'){
        $filtro->sortBy = 'name';
    }
    if($fragUrl[3] == 'movies'){
        $filtro->sortBy = 'releaseDate';
    }
    if($fragUrl[3] == 'reviews'){
        $filtro->sortBy = 'stars';
    }
    if($fragUrl[3] == 'genres'){
        $filtro->sortBy = 'title';
    }
}
function sendMessage($message){
    $dataMessage = ['message' => $message];
    echo json_encode($dataMessage);
}
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if(validar($header) == true){
            if(count($fragUrl) == 4){
                echo json_encode(getAllItems($fragUrl[3], $filtro));
            }else{
                echo json_encode(getItem($fragUrl[3], $filtro, $fragUrl[4]));
            }
        }
        break;
    
    case 'POST':
        if($url == '/api/v1/auth/signup'){
            $user = ['password' => $body->password, 'email' => $body->email, 'name' => $body->name, 'username' => $body->username];
            $result = createUser((object) $user);
            echo json_encode(['token' => $result->token ]);
        }
        if($url == '/api/v1/signin'){
            $user = ['password' => $body->password, 'email' => $body->email];
            $result = postLogin((object) $user);
            echo json_encode(['token' => $result->token ]);
        }
        if(validar($header) == true){
            if($fragUrl[3] == 'reviews' && $fragUrl[4] != 'evaluations'){
                $review = ['content' => $body->content, 'stars' => $body->stars];
                postReview($_COOKIE['user_id'], $fragUrl[4], (object) $review);
            }
            if($fragUrl[3] == 'reviews' && $fragUrl[4] == 'evaluations'){
                $result = postEvaluation($_COOKIE['user_id'], $fragUrl[5], $body->positive);
            }
        }
        
        break;
        
        case 'DELETE':
            if(validar($header) == true){
                if($url == '/api/v1/auth/signout'){
                    
                }
                if($fragUrl[3] == 'reviews' && $fragUrl[4] != 'evaluations'){
                    $review = ['content' => $body->content, 'stars' => $body->stars];
                    postReview($_COOKIE['user_id'], $fragUrl[4], (object) $review);
                }
                if($fragUrl[3] == 'reviews' && $fragUrl[4] == 'evaluations'){
                    $result = postEvaluation($_COOKIE['user_id'], $fragUrl[5], $body->positive);
                }
            }
        break;
    
    default:
        http_response_code(404);
        break;
}

