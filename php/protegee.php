<?php
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// Page accessible uniquement aux utilisateurs authentifiés
ll_verifie_authentification();

// génération de la page
ll_aff_entete('Page accessible uniquement aux utilisateurs authentifiés');

echo '<main><section>',
        '<h2>Utilisateur : ', ll_html_proteger_sortie($_SESSION['user']['pseudo']), '</h2>';


echo '<p>SID : ', session_id(),
     '</p>',
     '<h3>Données mémorisées dans la table utilisateur</h3>',
     '<ul>';

$bd = ll_bd_connecter();

$S =   "SELECT *
        FROM utilisateur
        WHERE utPseudo = '{$_SESSION['user']['pseudo']}'";

$R = mysqli_query($bd, $S) or ll_bd_erreur($bd, $S);

$enr = mysqli_fetch_assoc($R);

// Libération de la mémoire associée au résultat de la requête
mysqli_free_result($R);

// fermeture de la connexion à la base de données
mysqli_close($bd);


$enr = ll_html_proteger_sortie($enr);

foreach($enr as $key => $value){
    echo '<li>', $key, ' : ', $value, '</li>';
}

echo '</ul>';


echo '</section></main>';
ll_aff_pied();

ob_end_flush();
?>
