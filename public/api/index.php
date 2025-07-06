<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Authorization, X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header('Access-Control-Allow-Methods: POST, GET, PATCH, DELETE');
header("Allow: GET, POST, PATCH, DELETE");

date_default_timezone_set('America/Argentina/Buenos_Aires');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {    
   return 0;    
}  

spl_autoload_register(
    function ($nombre_clase) {
        include __DIR__.'/'.str_replace('\\', '/', $nombre_clase) . '.php';
    }
);

use \Firebase\JWT\JWT;

require_once 'config_db.php';
require_once 'config_jwt.php';

// ----------------- ROUTER ------------------

$metodo = strtolower($_SERVER['REQUEST_METHOD']);
$comandos = explode('/', strtolower($_GET['comando']));
$funcionNombre = $metodo.ucfirst($comandos[0]);

$parametros = array_slice($comandos, 1);
if (count($parametros) >0 && $metodo == 'get') {
    $funcionNombre = $funcionNombre.'ConParametros';
}

if (function_exists($funcionNombre)) {
    call_user_func_array($funcionNombre, $parametros);
} else {
    header(' ', true, 400);
}

// ----------------- FUNCIONES DE SOPORTE ------------------

function output($val, $headerStatus = 200)
{
    header(' ', true, $headerStatus);
    header('Content-Type: application/json');
    print json_encode($val);
    die;
}

function outputError($codigo = 500)
{
    switch ($codigo) {
        case 400:
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad request", true, 400);
            die;
        case 401:
            header($_SERVER["SERVER_PROTOCOL"] . " 401 Unauthorized", true, 401);
            die;
        case 404:
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
            die;
        default:
            header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error", true, 500);
            die;
            break;
    }
}

function conectarBD()
{
    $link = mysqli_connect(DBHOST, DBUSER, DBPASS, DBBASE);
    if ($link === false) {
        outputError(500, "Falló la conexión: " . mysqli_connect_error());
    }
    mysqli_set_charset($link, 'utf8');
    return $link;
}

function autenticar($email, $clave)
{
    $link = conectarBD();
    $email = mysqli_real_escape_string($link, $email);
    $clave = mysqli_real_escape_string($link, $clave);
    $sql = "SELECT id, nombre_completo FROM usuarios WHERE email='$email' AND clave='$clave'";
    $resultado = mysqli_query($link, $sql);
    if ($resultado === false) {
        outputError(500, "Falló la consulta: " . mysqli_error($link));
    }

    $ret = false;    
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $ret = [
            'id'     => $fila['id'],
            'nombre' => $fila['nombre_completo'],
        ];
    }
    mysqli_free_result($resultado);
    mysqli_close($link);
    return $ret;
}


function requiereLogin()
{
    try {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            throw new Exception("Token requerido", 1);
        }
        list($jwt) = sscanf($headers['Authorization'], 'Bearer %s');
        $decoded = JWT::decode($jwt, JWT_KEY, [JWT_ALG]);
    } catch(Exception $e) {
        outputError(401);
    }
    return $decoded;
}

// ----------------- API ------------------

function getPrivado()
{
    $payload = requiereLogin();
    output(['data' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.']);
}

function getSysinfo()
{
    output(['info' => 'Información del sistema.']);
}


function getPerfil()
{
    $payload = requiereLogin();
    output(['id' => $payload->uid, 'nombre' => $payload->nombre]);
}

function postLogin()
{
    $loginData = json_decode(file_get_contents("php://input"), true);
    $logged = autenticar($loginData['email'], $loginData['clave']);

    if ($logged===false) {
        outputError(401);
    }
    $payload = [
        'uid'       => $logged['id'],
        'nombre'    => $logged['nombre'],
        'exp'       => time() + JWT_EXP,
    ];
    $jwt = JWT::encode($payload, JWT_KEY, JWT_ALG);
    output(['jwt'=>$jwt]);
}

function patchLogin()
{
    $payload = requiereLogin();
    $payload->exp = time() + JWT_EXP;
    $jwt = JWT::encode($payload, JWT_KEY);
    output(['jwt'=>$jwt]);
}
