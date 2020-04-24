<?php

require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// Page accessible uniquement aux utilisateurs authentifiés
ll_verifie_authentification();

$bd = ll_bd_connecter();

//recupère l'auteur du commentaire
$sql="SELECT coAuteur FROM commentaire WHERE coID = '{$_GET['id']}'";
$R = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
$r=mysqli_fetch_assoc($R);

//si l'utilisateur actuel est l'auteur ou s'il est rédacteur on supprime
if($r['coAuteur']==$_SESSION['user']['pseudo'] || $_SESSION['user']['redacteur']==true){
  $S =   "DELETE
          FROM commentaire
          WHERE coID = '{$_GET['id']}'";
  $R = mysqli_query($bd, $S) or ll_bd_erreur($bd, $S);
}
  // redirection sur la page source ou sur index sinon
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
header('Location: ' . $referer);
exit(); //===> Fin du script


ob_end_flush();

?>
