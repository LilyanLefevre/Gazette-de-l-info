<?php

require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();
//on verifie sur l'utilisateur est bien authentifié et qu'il est bien l'auteur
if(isset($_SESSION['user'])){
  header("Location: ../index.php");
  exit();
}
if(!isset($_SESSION['source'])){
  $_SESSION['source']=isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
}

// si formulaire soumis, traitement de la demande d'inscription
if (isset($_POST['btnConnexion'])) {
    $erreurs = cbl_traitement_connexion();
}
else{
    $erreurs = FALSE;
}
// génération de la page
ll_aff_entete('Connexion', 'Connexion');

cbl_aff_form($erreurs);

ll_aff_pied();

ob_end_flush(); //FIN DU SCRIPT


function cbl_traitement_connexion(){
	if( !ll_parametres_controle('post',array('pseudo','passe','btnConnexion'),array())) {
        ll_session_exit();
    }

    $erreurs = array();

     // ouverture de la connexion à la base
    $bd = ll_bd_connecter();

    // vérification de l'existence du pseudo
    $pseudoe=trim($_POST['pseudo']);
    $pseudoe = mysqli_real_escape_string($bd, $pseudoe);
    $sql = "SELECT utPseudo, utPasse, utStatut FROM utilisateur WHERE utPseudo = '{$pseudoe}'";
    $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
    if(mysqli_num_rows($res)!=1){
    	$erreurs[]='Echec d\'authentification. Utilisateur inconnu ou mot de passe incorrect.';
    	// Libération de la mémoire associée au résultat de la requête
    	mysqli_free_result($res);
    	return($erreurs);
    }

    $tab = mysqli_fetch_assoc($res);
    if(password_verify($_POST['passe'],$tab['utPasse'])!=true){
    	$erreurs[]='Echec d\'authentification. Utilisateur inconnu ou mot de passe incorrect.';
    	// Libération de la mémoire associée au résultat de la requête
    	mysqli_free_result($res);
   		return($erreurs);
    }


    $_SESSION['user']['pseudo']=ll_html_proteger_sortie($tab['utPseudo']);
    $_SESSION['user']['redacteur']=false;
    $_SESSION['user']['administrateur']=false;
    if($tab['utStatut']==1){
    	$_SESSION['user']['redacteur']=true;
    }
    if($tab['utStatut']==2){
    	$_SESSION['user']['administrateur']=true;
    }
    if($tab['utStatut']==3){
    	$_SESSION['user']['redacteur']=true;
    	$_SESSION['user']['administrateur']=true;
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);

    // redirection sur la page source ou sur index sinon
    header('Location: ' . $_SESSION['source']);
    exit(); //===> Fin du script
}

function cbl_aff_form($erreurs) {
    // affectation des valeurs à afficher dans les zones du formulaire
    if (isset($_POST['btnConnexion'])){
        $pseudo = ll_html_proteger_sortie(trim($_POST['pseudo']));
    }else{
        $pseudo ='';
    }

     echo
        '<main>',
        '<section>',
            '<h2>Formulaire de connexion</h2>',
            '<p>Pour vous connecter, remplissez le formulaire ci-dessous.</p>',
            '<form action="connexion.php" method="post">';

    if ($erreurs) {
        echo '<div class="erreur">';
        foreach ($erreurs as $err) {
            echo $err;
        }
        echo '</div>';
    }

    echo '<table>';
    ll_aff_ligne_input('text', 'Pseudo :', 'pseudo', $pseudo, array('required' => 0));
    ll_aff_ligne_input('password', 'Mot de passe :', 'passe', '', array('required' => 0));

    echo    '</td></tr>',
            '<tr>',
                '<td colspan="2">',
                    '<input type="submit" name="btnConnexion" value="Se connecter">',
                    '<input type="reset" value="Annuler">',
                '</td>',
            '</tr>',
        '</table>',
        '</form>',
        '<p>Pas encore inscrit ? N\'attendez pas, <a href="./inscription.php">inscrivez-vous</a> !</p>',
        '</section></main>';
}




?>
