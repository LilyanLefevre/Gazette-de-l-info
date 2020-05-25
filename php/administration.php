<?php

require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();


if(!ll_verifie_authentification() || $_SESSION['user']['administrateur']!=true){
	header("Location: ../index.php");
  exit();
}

// génération de la page
ll_aff_entete('Administration', 'Administration');
//variables globales qui valent 1 en cas de succes
$success=0;

if(isset($_POST['btnValider'])){
	$erreur=cbl_traitement_admin();
	if(empty($erreur)==true){
		$success=1;
	}
}else{
	$erreur=false;
}


cbl_aff_admin($erreur);

ll_aff_pied();

ob_end_flush(); //FIN DU SCRIPT

/**
 * Affiche une liste déroulante à partir des options passées en paramètres et désactive la paramètre par défaut.
 *
 * @param string    $nom       Le nom de la liste déroulante (valeur de l'attribut name)
 * @param array     $options   Un tableau associatif donnant la liste des options sous la forme valeur => libelle
 * @param string    $default   La valeur qui doit être sélectionnée par défaut.
 */
function ll_cbl_aff_liste_modifie($nom, $options, $defaut) {
    echo '<select name="', $nom, '">';
    foreach ($options as $valeur => $libelle) {
        echo '<option value="', $valeur, '"', (($defaut == $valeur) ? ' selected disabled' : '') ,'>', $libelle, '</option>';
    }
    echo '</select>';
}

function cbl_aff_admin($erreur){
	global $success;
	$bd = ll_bd_connecter();
	$pseudo=mysqli_real_escape_string($bd, $_SESSION['user']['pseudo']);


	$sql ="SELECT utPseudo, utStatut,COUNT(DISTINCT coID),COUNT(DISTINCT arID),nbCom
	FROM `utilisateur`
	LEFT OUTER JOIN `commentaire` ON utPseudo = coAuteur
	LEFT OUTER JOIN `article` ON utPseudo = arAuteur
	LEFT OUTER JOIN (
	    SELECT arAuteur AS maxCom,COUNT(coArticle) AS nbCom
		FROM `article`
		LEFT OUTER JOIN `commentaire` ON arID = coArticle
		GROUP BY arAuteur
	    ) AS max ON utPseudo = max.maxCom
	GROUP BY utPseudo
	ORDER BY utStatut DESC
	";

	$res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
	$statut[0]='Utilisateur simple';
	$statut[1]='Rédacteur';
	$statut[2]='Administrateur';
	$statut[3]='Rédacteur et administrateur';

	echo   '<main>',
	         '<section>',
	            '<h2>Liste des utilisateurs</h2>';

	ll_aff_erreur_success($erreur,$success);
	echo '<form action="administration.php" method="POST">',
	            '<table id="listeUtilisateurs">',
	            '<tr>',
				    '<td>Pseudo</td>',
				    '<td>Statut</td>',
				    '<td>Nombre de commentaires publiés</td>',
				    '<td>Nombre d\'articles publiés</td>',
				    '<td>Nombre moyen de commentaires portant sur les articles qu\'il a publié</td>',
				'</tr>';

	while($fetch=mysqli_fetch_assoc($res)){
		echo '<tr>',
		'<td>',ll_html_proteger_sortie($fetch['utPseudo']),'</td>',
		'<td>',ll_cbl_aff_liste_modifie(ll_html_proteger_sortie($fetch['utPseudo']),$statut,ll_html_proteger_sortie($fetch['utStatut'])),'</td>',
		'<td>',ll_html_proteger_sortie($fetch['COUNT(DISTINCT coID)']),'</td>',
		'<td>',ll_html_proteger_sortie($fetch['COUNT(DISTINCT arID)']),'</td>';
		if(ll_html_proteger_sortie($fetch['COUNT(DISTINCT arID)'])!=0){
			echo '<td>',ll_html_proteger_sortie($fetch['nbCom'])/(double)ll_html_proteger_sortie($fetch['COUNT(DISTINCT arID)']),'</td>';
		}else{
			echo '<td>0</td>';
		}
		'</tr>';
	}

 echo    '</td></tr>',
            '<tr>',
                '<td colspan="5">',
                    '<input type="submit" name="btnValider" value="Valider">',
                    '<input type="reset" value="Annuler">',
                '</td>',
            '</tr>',
        '</table>',
        '</form>',
        '</section></main>';

	mysqli_free_result($res);
	mysqli_close($bd);
}


function cbl_traitement_admin(){
	$erreurs=array();
	$bd = ll_bd_connecter();

	foreach ($_POST as $key => $value) {
		if($value!=0 && $value!=1 && $value!=2 && $value!=3){
			$erreurs[]="Statut incorrect";
			return $erreurs;
		}
		$pseudo=$key;
		$sql="UPDATE utilisateur SET utStatut = '{$value}' WHERE utPseudo = '{$pseudo}'";
		mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
		if($value==1 || $value==3){
			$sql="INSERT INTO `redacteur`(`rePseudo`, `reBio`, `reCategorie`, `reFonction`) VALUES ('{$pseudo}',' ','1',NULL)";
			mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
		}
	}

	mysqli_close($bd);

	$success=1;
}


?>
