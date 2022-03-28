<?php
require_once 'conexion.php';
require_once 'jwt.php';

/********BLOQUE DE ACCESO DE SEGURIDAD */
$headers = apache_request_headers();
$tmp = $headers['Authorization'];
$jwt = str_replace("Bearer ", "", $tmp);
if(JWT::verify($jwt, Config::SECRET) > 0){
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

$user = JWT::get_data($jwt, Config::SECRET)['user'];
/*** BLOQUE WEB SERVICE REST */
$metodo = $_SERVER["REQUEST_METHOD"];
switch($metodo){
    case 'GET':
            $c = conexion();
            if(isset($_GET['id_alert'])){
                $s = $c->prepare("SELECT * FROM alert WHERE id_alert = :id_alert");
                $s->bindValue(":id_alert", $_GET['id_alert']);
            }else{
                $s = $c->prepare("SELECT * FROM alert");
            }
            $s->execute();
            $s->setFetchMode(PDO::FETCH_ASSOC);
            $r = $s->fetchAll();
            header("http/1.1 200 ok");
            echo json_encode($r);
        break;
    case 'POST':
        if(isset($_POST['n_student']) && isset($_POST['ouser'])){
            $c = conexion();
            $s = $c->prepare("INSERT INTO alert (user, n_student, ouser, hrfecha) VALUES (:u, :ns, :o, NOW())");
            $s->bindValue(":u", $user);
            $s->bindValue(":ns", $_POST['n_student']);
            $s->bindValue(":o", $_POST['ouser']);
            $s->execute();
            if($s->rowCount()>0){
                header("http/1.1 201 created");
                echo json_encode(array("add" => "y", "id_alert" => $c->lastInsertId()));
            }else{
                header("http/1.1 400 bad request");
                echo json_encode(array("add" => "n"));
            }
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo "Faltan datos";
        }
        break;
    case 'PUT':
        if(isset($_GET['id_alert']) ){
            $sql = "UPDATE alert SET ";
            (isset($_GET['user'])) ? $sql .= "user = :u, " : null;
            (isset($_GET['n_student'])) ? $sql .= "n_student = :ns, " : null;
            (isset($_GET['ouser'])) ? $sql .= "ouser = :o, " : null;
            (isset($_GET['hrfecha'])) ? $sql .= "hrfecha = :h, " : null;
            $sql = substr($sql, 0, -2);
            $sql .= " WHERE id_alert = :id_alert";
            $c = conexion();
            $s = $c->prepare($sql);
            (isset($_GET['user'])) ? $s->bindValue(":u", $_GET['user']) : null;
            (isset($_GET['n_student'])) ? $s->bindValue(":ns", $_GET['n_student']) : null;
            (isset($_GET['ouser'])) ? $s->bindValue(":o", $_GET['ouser']) : null;
            (isset($_GET['hrfecha'])) ? $s->bindValue(":h", $_GET['hrfecha']) : null;

            $s->bindValue(":id_alert", $_GET['id_alert']);
            $s->execute();
            if($s->rowCount()>0){
                header("http/1.1 200 ok");
                echo json_encode(array("update" => "y"));
            }else{
                header("http/1.1 400 bad request");
                echo json_encode(array("update" => "n"));
            }
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo "Faltan datos";
        }
        break;
    case 'DELETE':
        if(isset($_GET['id_alert'])){
            $c = conexion();
            $s = $c->prepare("DELETE FROM alert WHERE id_alert = :id_alert");
            $s->bindValue(":id_alert", $_GET['id_alert']);
            $s->execute();
            if($s->rowCount()>0){
                header("http/1.1 200 ok");
                echo json_encode(array("delete" => "y"));
            }else{
                header("http/1.1 400 bad request");
                echo json_encode(array("delete" => "n"));
            }
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo "Faltan datos";
        }
        break;
    default:
        header("HTTP/1.1 405 Method Not Allowed");
}