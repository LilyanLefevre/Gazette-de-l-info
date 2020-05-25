<?php

require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();


if(!ll_verifie_authentification() || $_SESSION['user']['redacteur']!=true){
	header("Location: ../index.php");
  exit();
}

// génération de la page
ll_aff_entete('Nouvel article', 'Nouvel article');

$erreurs=NULL;
if(isset($_POST['btnValider'])){
	$erreurs=cbl_traitement_nouveau();
}

cbl_aff_form($erreurs);

ll_aff_pied();

ob_end_flush(); //FIN DU SCRIPT


function cbl_aff_input_file($name,$label,$type='file'){
	echo '<tr>',
	'<td><label for="',$name,'">',$label,'</label></td>',
	'<td><input type="',$type,'"',
	'id="',$name,'" name="',$name,'"',
	'accept="image/jpeg"></td>',
	'</tr>';
}

function cbl_aff_form($erreurs) {
    // affectation des valeurs à afficher dans les zones du formulaire
    if (isset($_POST['btnValider'])){
    	$titre = (trim($_POST['titre']));
        $resumer = (trim($_POST['resume']));
        $texte = (trim($_POST['texte']));
    }else{
        $titre ='';
        $resumer ='';
        $texte ='';
    }

     echo
        '<main>',
        '<section>',
            '<h2>Nouvel article</h2>',
            '<p>Pour créer un nouvel article, remplissez le formulaire ci-dessous.</p>',
            '<form action="nouveau.php" method="post" enctype="multipart/form-data">',
            '<input type="hidden" name="MAX_FILE_SIZE" value="1024000">';

			//affichage des éventuelles erreurs commises lors de la soumission du formulaire
		  if ($erreurs) {
		      echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de vos modifications :<ul>';
		      foreach ($erreurs as $err) {
		          echo '<li>', $err, '</li>';
		      }
		      echo '</ul></div>';
		  }

    echo '<table>';
    ll_aff_ligne_input('text', 'Titre :', 'titre', $titre, array('required' => 0));
    ll_aff_input_textarea('resume','Résumé :',4,40,$resumer,'editer');
    ll_aff_input_textarea('texte','Texte :',10,60,$texte,'editer');
    cbl_aff_input_file('imgArticle','Image de l\'article');

    echo    '</td></tr>',
            '<tr>',
                '<td colspan="2">',
                    '<input type="submit" name="btnValider" value="Valider">',
                    '<input type="reset" value="Annuler">',
                '</td>',
            '</tr>',
        '</table>',
        '</form>',
        '</section></main>';
}



function cbl_traitement_nouveau(){

	  //verification des clés présentent dans POST
	if( !ll_parametres_controle('post',array('titre','resume','texte','MAX_FILE_SIZE','btnValider'),array())) {
        ll_session_exit();
    }

  $titre = trim($_POST['titre']);
  $resume = trim($_POST['resume']);
  $texte = trim($_POST['texte']);

	$erreurs = array();

	ll_verifier_texte_article($titre,'Le titre',$erreurs);
	ll_verifier_texte_article($resume,'Le résumer',$erreurs);
	ll_verifier_texte_article($texte,'Le texte',$erreurs);



	if (isset($_FILES['imgArticle'])) {

		// Vérification si erreurs
		$f = $_FILES['imgArticle'];
		switch ($f['error']) {
		case 1:
		case 2:
			$erreurs[] = $f['name'].' est trop gros.';
			break;
		case 3:
			$erreurs[] = 'Erreur de transfert de '.$f['name'];
			break;
		case 4:
			$erreurs[] = 'Image introuvable.';
		}
	}
	//2 correspond au format JPG
  if(ll_verif_img($_FILES['imgArticle']['tmp_name'],4,3,2)==FALSE){
    $erreurs[] = 'La photo doit être au format .jpg et être au format 4:3.';
  }

	if(count($erreurs)>0){
		return $erreurs;
	}



	$bd = ll_bd_connecter();
	//on récupère la date actuelle
  date_default_timezone_set('Europe/Paris');
	$date=date('YmdHi');
	$titreSecu=mysqli_real_escape_string($bd, $titre);
	$resumeSecu=mysqli_real_escape_string($bd, $resume);
	$texteSecu=mysqli_real_escape_string($bd, $texte);
	$pseudo=mysqli_real_escape_string($bd, $_SESSION['user']['pseudo']);

	$sql = "INSERT INTO `article` (`arID`, `arTitre`, `arResume`, `arTexte`, `arDatePublication`, `arDateModification`, `arAuteur`) VALUES (NULL, '{$titreSecu}', '{$resumeSecu}', '{$texteSecu}', '{$date}', NULL, '{$pseudo}');";
	mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

	$sql= "SELECT arID
	FROM article
	WHERE arTitre='{$titreSecu}' AND arDatePublication='{$date}' AND arAuteur='{$pseudo}'";
	$res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

	if(mysqli_num_rows($res)<1){
		return $erreurs[]='L\'insertion de l\'article dans la base de donnée a échoué';
	}


	if (isset($_FILES['imgArticle'])){
		// Pas d'erreur => placement du fichier
		if (! @is_uploaded_file($f['tmp_name'])) {
			return $erreurs[]='Erreur lors du transfert de l\'image';
		}

		$fetch=mysqli_fetch_assoc($res);
		$id=$fetch['arID'];

		$place ="../upload/{$id}.jpg";
		if (@move_uploaded_file($f['tmp_name'], $place)) {
			header('Location: ../index.php');
		} else {
			return $erreurs[] = 'Erreur interne de transfert';
		}
	}

	mysqli_free_result($res);
	mysqli_close($bd);

	return NULL;
}
?>
