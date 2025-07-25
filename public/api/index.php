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
    $sql = "SELECT id, nombre_completo, id_rol FROM usuarios WHERE email='$email' AND clave='$clave'";
    $resultado = mysqli_query($link, $sql);
    if ($resultado === false) {
        outputError(500, "Falló la consulta: " . mysqli_error($link));
    }

    $ret = false;    
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $ret = [
            'id'     => $fila['id'],
            'nombre' => $fila['nombre_completo'],
            'rol'    => $fila['id_rol'],
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
        'rol'       => $logged['rol'],
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

function getPerfil()
{
    $payload = requiereLogin();
    $id = $payload->uid;

    $link = conectarBD();
    $sql = "SELECT u.id, u.nombre_completo, u.email, u.descripcion, f.nombre AS foto_perfil
            FROM usuarios u
            LEFT JOIN fotos_perfil f ON u.id_foto = f.id
            WHERE u.id = $id";
    $res = mysqli_query($link, $sql);

    if ($fila = mysqli_fetch_assoc($res)) {
        output($fila);
    } else {
        outputError(404, "Usuario no encontrado");
    }

    mysqli_free_result($res);
    mysqli_close($link);
}

function getUsuarios()
{
    $link = conectarBD();
    $sql = "SELECT u.id, u.email, u.nombre_completo, u.descripcion, f.nombre AS foto_perfil
            FROM usuarios u
            LEFT JOIN fotos_perfil f ON u.id_foto = f.id";
    $resultado = mysqli_query($link, $sql);

    $usuarios = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $id_usuario = $fila['id'];

        $sqlPub = "SELECT id FROM publicaciones WHERE usuario_id = $id_usuario";
        $resPub = mysqli_query($link, $sqlPub);
        $publicaciones = [];

        while ($pub = mysqli_fetch_assoc($resPub)) {
            $publicaciones[] = $pub;
        }

        $fila['publicaciones'] = $publicaciones;
        $usuarios[] = $fila;
    }

    mysqli_free_result($resultado);
    mysqli_close($link);

    output($usuarios);
}


function getUsuario($id) {
    $link = conectarBD();
    $id = mysqli_real_escape_string($link, $id);

    $sql = "SELECT u.id, u.email, u.nombre_completo, u.descripcion, f.nombre AS foto_perfil
            FROM usuarios u
            LEFT JOIN fotos_perfil f ON u.id_foto = f.id
            WHERE u.id = $id";
    $res = mysqli_query($link, $sql);

    if ($fila = mysqli_fetch_assoc($res)) {
        output($fila);
    } else {
        outputError(404, "Usuario no encontrado");
    }

    mysqli_free_result($res);
    mysqli_close($link);
}



function postUsuarios()
{
    $data = json_decode(file_get_contents("php://input"), true);
    $nombre = $data['nombre'];
    $email = $data['email'];
    $clave = $data['clave'];

    $link = conectarBD();
    $nombre = mysqli_real_escape_string($link, $nombre);
    $email = mysqli_real_escape_string($link, $email);
    $clave = mysqli_real_escape_string($link, $clave);

    $sql = "INSERT INTO usuarios (nombre_completo, email, clave) VALUES ('$nombre', '$email', '$clave')";
    $resultado = mysqli_query($link, $sql);

    if ($resultado) {
        output(['mensaje' => 'Usuario creado correctamente']);
    } else {
        outputError(500);
    }
}

function patchUsuario() {
    $payload = requiereLogin();
    $data = json_decode(file_get_contents("php://input"), true);

    $nombre = $data['nombre_completo'] ?? '';
    $descripcion = $data['descripcion'] ?? '';
    $id_foto = $data['id_foto'] ?? null;

    $link = conectarBD();
    $nombre = mysqli_real_escape_string($link, $nombre);
    $descripcion = mysqli_real_escape_string($link, $descripcion);
    $usuario_id = $payload->uid;

    $sql = "UPDATE usuarios SET nombre_completo='$nombre', descripcion='$descripcion'";

    if ($id_foto !== null) {
        $id_foto = intval($id_foto);
        $sql .= ", id_foto=$id_foto";
    }

    $sql .= " WHERE id=$usuario_id";

    if (!mysqli_query($link, $sql)) {
        outputError(500);
    }

    mysqli_close($link);
    error_log("Actualizando perfil del usuario $usuario_id");
    output(['success' => true]);
}


function getFotosPerfil() {
    $link = conectarBD();
    $sql = "SELECT id, nombre FROM fotos_perfil";
    $res = mysqli_query($link, $sql);

    $fotos = [];
    while ($fila = mysqli_fetch_assoc($res)) {
        $fotos[] = $fila;
    }

    mysqli_free_result($res);
    mysqli_close($link);

    output($fotos);
}



function postRegister()
{
    $data = json_decode(file_get_contents("php://input"), true);
    $nombre = $data['nombre'];
    $email = $data['email'];
    $clave = $data['clave'];
    $id_rol = $data['id_rol'];

    $link = conectarBD();
    $nombre = mysqli_real_escape_string($link, $nombre);
    $email = mysqli_real_escape_string($link, $email);
    $clave = mysqli_real_escape_string($link, $clave);
    $id_rol = mysqli_real_escape_string($link, $id_rol);
    $id_foto = 1;

    $sql = "INSERT INTO usuarios (nombre_completo, email, clave, id_foto, id_rol) 
            VALUES ('$nombre', '$email', '$clave', $id_foto, $id_rol)";
    $ok = mysqli_query($link, $sql);

    if ($ok) {
        output(['mensaje' => 'Usuario registrado correctamente']);
    } else {
        outputError(500);
    }

    mysqli_close($link);
}

function getRoles()
{
    $link = conectarBD();
    $sql = "SELECT id, nombre FROM roles";
    $res = mysqli_query($link, $sql);
    $roles = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $roles[] = $row;
    }

    mysqli_close($link);
    output($roles);
}


function getPublicaciones() {
    $link = conectarBD();

    $sql = "SELECT p.id, p.titulo, p.contenido, p.fecha, p.usuario_id, u.nombre_completo AS autor
            FROM publicaciones p
            JOIN usuarios u ON p.usuario_id = u.id
            ORDER BY p.fecha DESC";

    $resultado = mysqli_query($link, $sql);
    if ($resultado === false) {
        outputError(500);
    }

    $publicaciones = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $publicaciones[] = $fila;
    }

    mysqli_free_result($resultado);
    mysqli_close($link);

    output($publicaciones);
}

function postPublicaciones() {
    $payload = requiereLogin(); 
    $data = json_decode(file_get_contents("php://input"), true);
    $titulo = trim($data['titulo'] ?? '');
    $contenido = trim($data['contenido'] ?? '');
    $id_usuario = $payload->uid;

    if ($titulo === '' || $contenido === '') {
        outputError(400, 'El título y el contenido no pueden estar vacíos');
    }

    $link = conectarBD();
    $titulo = mysqli_real_escape_string($link, $titulo);
    $contenido = mysqli_real_escape_string($link, $contenido);

    $sql = "INSERT INTO publicaciones (titulo, contenido, usuario_id) VALUES ('$titulo', '$contenido', $id_usuario)";
    if (!mysqli_query($link, $sql)) {
        outputError(500);
    }

    mysqli_close($link);

    output(['success' => true]);
}

function getPublicacionesUsuario()
{
    $payload = requiereLogin();
    $usuario_id = $payload->uid;

    $link = conectarBD();
    $usuario_id = mysqli_real_escape_string($link, $usuario_id);

    $sql = "SELECT p.id, p.titulo, p.contenido, u.nombre_completo AS nombre_usuario
            FROM publicaciones p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.usuario_id = '$usuario_id'
            ORDER BY p.id DESC";

    $resultado = mysqli_query($link, $sql);
    if ($resultado === false) {
        outputError(500);
    }

    $publicaciones = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $publicaciones[] = $fila;
    }

    mysqli_free_result($resultado);
    mysqli_close($link);
    output($publicaciones);
}

function getMisPublicaciones() {
    $payload = requiereLogin();
    $id_usuario = $payload->uid;

    $link = conectarBD();
    $sql = "SELECT p.*, u.nombre_completo AS nombre_usuario
            FROM publicaciones p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.usuario_id = $id_usuario
            ORDER BY p.id DESC";
    $res = mysqli_query($link, $sql);

    $data = [];
    while ($fila = mysqli_fetch_assoc($res)) {
        $data[] = $fila;
    }

    mysqli_free_result($res);
    mysqli_close($link);
    output($data);
}

function patchPublicaciones($id) {
    $payload = requiereLogin();
    $data = json_decode(file_get_contents("php://input"), true);
    $titulo = trim($data['titulo'] ?? '');
    $contenido = trim($data['contenido'] ?? '');

    if ($titulo === '' || $contenido === '') {
        outputError(400, 'El título y el contenido no pueden estar vacíos');
    }

    $link = conectarBD();
    $titulo = mysqli_real_escape_string($link, $titulo);
    $contenido = mysqli_real_escape_string($link, $contenido);
    $id = mysqli_real_escape_string($link, $id);
    $usuario_id = $payload->uid;

    $sql = "UPDATE publicaciones SET titulo='$titulo', contenido='$contenido'
            WHERE id=$id AND usuario_id=$usuario_id";

    if (!mysqli_query($link, $sql)) {
        outputError(500);
    }

    mysqli_close($link);
    error_log("Editando publicación ID: $id por usuario $usuario_id");
    output(['success' => true]);
}

/*function deletePublicaciones($id) {
    $payload = requiereLogin();
    $id = mysqli_real_escape_string(conectarBD(), $id);
    $usuario_id = $payload->uid;

    $link = conectarBD();
    $sql = "DELETE FROM publicaciones WHERE id=$id AND usuario_id=$usuario_id";
    $res = mysqli_query($link, $sql);

    if (!$res) {
        outputError(500);
    }

    mysqli_close($link);
    output(['success' => true]);
}*/
 /*
function deletePublicaciones($id) {
    $payload = requiereLogin();
    $id = mysqli_real_escape_string(conectarBD(), $id);
    $usuario_id = $payload->uid;

    $link = conectarBD();
    $sql = "DELETE FROM publicaciones WHERE id=$id AND usuario_id=$usuario_id";
    $res = mysqli_query($link, $sql);

    if (!$res) {
        outputError(500);
    }

    mysqli_close($link);
    output(['success' => true]);
}  */


function deletePublicaciones($id) {
    $payload = requiereLogin();
    $usuario_id = $payload->uid;
    $rol = $payload->rol; // ← Asegurate de que esto exista

    $link = conectarBD();
    $id = mysqli_real_escape_string($link, $id);
    $usuario_id = mysqli_real_escape_string($link, $usuario_id);

    // Si es admin, puede borrar cualquier publicación
    if ((int)$rol === 2) {
        $sql = "DELETE FROM publicaciones WHERE id = $id";
    } else {
        // Solo puede borrar publicaciones propias
        $sql = "DELETE FROM publicaciones WHERE id = $id AND usuario_id = $usuario_id";
    }

    $res = mysqli_query($link, $sql);

    if (!$res) {
        outputError(500);
    }

    mysqli_close($link);
    output(['success' => true]);
}

/*function deleteTodasLasPublicaciones() {
    $payload = requiereLogin();
    $rol = $payload->rol;

    // Solo el rol 2 (admin) puede hacer esto
    if ($rol !== 2) {
        outputError(403, "No tenés permiso para borrar todas las publicaciones.");
    }

    $link = conectarBD();
    $sql = "DELETE FROM publicaciones";
    $res = mysqli_query($link, $sql);

    if (!$res) {
        outputError(500, "No se pudieron borrar las publicaciones.");
    }

    mysqli_close($link);
    output(['success' => true]);
}*/



function postComentarios() {
    $payload = requiereLogin();
    $data = json_decode(file_get_contents("php://input"), true);
    $contenido = $data['contenido'] ?? '';
    $id_publicacion = $data['id_publicacion'] ?? null;

    if (!$id_publicacion || !$contenido) {
        outputError(400);
    }

    $link = conectarBD();
    $contenido = mysqli_real_escape_string($link, $contenido);
    $id_publicacion = (int) $id_publicacion;
    $id_usuario = $payload->uid;

    $sql = "INSERT INTO comentarios (id_publicacion, id_usuario, contenido)
            VALUES ($id_publicacion, $id_usuario, '$contenido')";
    
    if (!mysqli_query($link, $sql)) {
        outputError(500);
    }

    mysqli_close($link);
    output(['success' => true]);
}

function getComentariosConParametros($id_publicacion) {
    $link = conectarBD();
    $id_publicacion = (int) $id_publicacion;

    $sql = "SELECT c.id, c.contenido, c.fecha, c.id_usuario, u.nombre_completo AS autor
            FROM comentarios c
            JOIN usuarios u ON c.id_usuario = u.id
            WHERE c.id_publicacion = $id_publicacion
            ORDER BY c.fecha ASC";

    $res = mysqli_query($link, $sql);
    if (!$res) {
        outputError(500);
    }

    $comentarios = [];
    while ($fila = mysqli_fetch_assoc($res)) {
        $comentarios[] = $fila;
    }

    mysqli_free_result($res);
    mysqli_close($link);
    output($comentarios);
}

/*function deleteComentarios($id) {
    $payload = requiereLogin();
    $id = (int) $id;
    $id_usuario = $payload->uid;

    $link = conectarBD();

    // Verificar que el comentario exista y sea del usuario
    $sqlCheck = "SELECT * FROM comentarios WHERE id = $id AND id_usuario = $id_usuario";
    $resCheck = mysqli_query($link, $sqlCheck);
    
    if (mysqli_num_rows($resCheck) === 0) {
        mysqli_close($link);
        outputError(403); // No autorizado o no existe
    }

    // Eliminar el comentario
    $sql = "DELETE FROM comentarios WHERE id = $id AND id_usuario = $id_usuario";
    $res = mysqli_query($link, $sql);

    if (!$res) {
        mysqli_close($link);
        outputError(500);
    }

    mysqli_close($link);
    output(['success' => true]);
} */

function deleteComentarios($id) {
    $payload = requiereLogin();
    $id = (int) $id;
    $id_usuario = $payload->uid;
    $rol = (int) $payload->rol;

    $link = conectarBD();

    if ($rol === 2) {
        $sql = "DELETE FROM comentarios WHERE id = $id";
    } else {
        $sql = "DELETE FROM comentarios WHERE id = $id AND id_usuario = $id_usuario";
    }

    $res = mysqli_query($link, $sql);

    if (!$res || mysqli_affected_rows($link) === 0) {
        mysqli_close($link);
        outputError(403, "No se pudo borrar el comentario. No existe o no tenés permisos.");
    }

    mysqli_close($link);
    output(['success' => true]);
}
