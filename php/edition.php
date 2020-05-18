<?php

require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

$bd = ll_bd_connecter();

//on verifie sur l'utilisateur est bien authentifié et qu'il est bien l'auteur
ll_verifie_authentification();
$article=ll_verifie_auteur($bd);

//variable globale qui valent 1 si la soumission d'un des formulaires réussi
$suppressionSuccess=0;
$annulerSuccess=0;
$editionSuccess=0;

ll_aff_entete('Edition', 'Edition');

// si formulaire soumis, traitement de la demande d'inscription
if (isset($_POST['btnEditer'])) {
    $erreursEdition = ll_traitement_edition($bd);
    if(empty($erreursEdition)){
      $editionSuccess=1;
    }
}
else{
    $erreursEdition = FALSE;
}

// si formulaire soumis, traitement de la demande d'inscription
if (isset($_POST['btnSupprimer'])) {
    ll_traitement_suppression($bd);
    $suppressionSuccess=1;
}

// si formulaire soumis, traitement de la demande d'inscription
if (isset($_POST['btnAnnuler'])) {
    $annulerSuccess=1;
}

echo '<main>';
ll_aff_edition($article,$erreursEdition);
ll_aff_supprimer();

echo '</main>';

ll_aff_pied();

ob_end_flush();

/**
 * fonction qui affiche l'éventuel succès de l'édition
 * et le formulaire d'édition de l'article
 *
 * @param Array $article
 * @param Array $erreursEdition
 */
function ll_aff_edition($article,$erreursEdition){
  global $suppressionSuccess;
  if($suppressionSuccess==0){
    global $editionSuccess;
    echo '<section>',
            '<h2 id="edition">Editer l\'article</h2>';

    //si l'édition réussie on affiche le succès
    if($editionSuccess==1){
      echo '<div class="success">Article édité avec succès.<ul>';
      echo '</ul></div>';
    }
    echo    '<p>Vous pouvez éditer votrer article ici :</p>';

    //on affiche le formulaire de l'article
    ll_aff_formulaire_article($article,$erreursEdition);

    echo  '</section>';
  }

}

/**
 * fonction qui affiche l'éventuel succès de la suppression ou le formulaire
 * de suppression l'article
 */
function ll_aff_supprimer(){
  global $suppressionSuccess,$annulerSuccess;
  echo '<section>',
          '<h2 id="supprimer">Supprimer l\'article</h2>';
  if($suppressionSuccess==1){
    echo '<div class="success">Article supprimé.<ul>';
    echo '</ul></div>';
  }else{
    if($annulerSuccess==1){
      echo '<div class="success">Annulation prise en compte.<ul>';
      echo '</ul></div>';
    }

    echo     'Vous pouvez suprimer votrer article ici :';
    ll_aff_dialog_delete();
  }
echo    '</section>';
}

/**
 * fonction qui verifie que l'utilisateur actuel est l'auteur de l'article
 *
 * @param Object $bd connecter à la bd
 *
 * @return Array un tableau avec toutes les infos sur l'article actuel
 */
function ll_verifie_auteur($bd){
  $sql="SELECT arAuteur, arTexte, arTitre,arResume FROM article WHERE arID={$_GET['id']}";
  $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

  $res=mysqli_fetch_assoc($res);
  if($_SESSION['user']['pseudo']!=$res['arAuteur']){
    header("Location: ../index.php");
    exit();
  }
  return $res;
}

/**
  * fonction qui affiche le formulaire de modification de l'Article et les erreurs
  * eventuelles
  *
  * En absence de soumission, $erreursEdition est égal à FALSE
  * Quand la modification échoue, $erreursEdition est un tableau de chaînes
  *
  *  @param mixed $erreursEdition
  *  @param Array $article tableau avec les infos. sur l'article
  */
function ll_aff_formulaire_article($article,$erreursEdition){
  $titre = ll_html_proteger_sortie(trim($article['arTitre']));
  $resume = ll_html_proteger_sortie(trim($article['arResume']));
  $texte = ll_html_proteger_sortie(trim($article['arTexte']));

  if ($erreursEdition) {
      echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de votre inscription :<ul>';
      foreach ($erreursEdition as $err) {
          echo '<li>', $err, '</li>';
      }
      echo '</ul></div>';
  }

  echo '<form action="edition.php?id=',$_GET['id'],'" method="post">',
        '<table>';
  ll_aff_input_textarea('titre',"Titre :",2,100,$titre,'editer');
  ll_aff_input_textarea('resume',"Résumé :",10,100,$resume,'editer');
  ll_aff_input_textarea('texte',"Texte :",35,100,$texte,'editer');


  echo    '<tr>',
              '<td colspan="2">',
                  '<input type="submit" name="btnEditer" value="Enregistrer">',
                  '<input type="reset" value="Réinitialiser">',
              '</td>',
          '</tr>',
      '</table>',
      '</form>',
      '</section>';

}

/**
  * fonction qui traite le formulaire de modification de l'Article et renvoie
  * les erreurs eventuelles
  *
  *  @param Object $bd
  *
  *  @return Array $erreursEdition
  */
function ll_traitement_edition($bd){
  $erreursEdition=array();

  // vérification des mots de passe
  $titre = mysqli_real_escape_string($bd,trim($_POST['titre']));
  $resume = mysqli_real_escape_string($bd,trim($_POST['resume']));
  $texte = mysqli_real_escape_string($bd,trim($_POST['texte']));

  ll_verifier_texte_article($titre,"Le titre",$erreursEdition,250);
  ll_verifier_texte_article($resume,"Le résumé",$erreursEdition);
  ll_verifier_texte_article($texte,"Le texte",$erreursEdition);

  // si erreurs --> retour
  if (count($erreursEdition) > 0) {
      return $erreursEdition;   //===> FIN DE LA FONCTION
  }

  $sql = "UPDATE `article` SET `arTitre`='{$titre}',`arResume`='{$resume}',`arTexte`='{$texte}' WHERE arID='{$_GET['id']}'";
  mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

}

/**
  * fonction qui traite le formulaire de suppression de l'Article
  *
  *  @param Object $bd
  */
function ll_traitement_suppression($bd){
  //on supprime tous les commentaires de l'article
  $sql1="DELETE FROM `commentaire` WHERE coArticle='{$_GET['id']}'";
  mysqli_query($bd, $sql1) or ll_bd_erreur($bd, $sql1);

  //ensuite on peut supprimer l'article
  $sql2 = "DELETE FROM `article` WHERE arID='{$_GET['id']}'";
  mysqli_query($bd, $sql2) or ll_bd_erreur($bd, $sql2);
}

/**
 * fonction qui affiche une boite de dialogue qui demande de confirmer ou d'Annuler
 * la supression d'un article
 */
function ll_aff_dialog_delete(){
  echo   '<a href="#openModal" class="bouton_dialog">Supprimer</a>
          <div id="openModal" class="modalDialog">
           <div>
             <a href="#close" title="Close" class="close">X</a>
             <p class="dialogtitle">Êtes-vous sûrs de vouloir supprimer cet article?</p>',
             '<form action="edition.php?id=',$_GET['id'],'" method="post">',
                 '<table>',
                      '<tr>',
                         '<td colspan="2">',
                             '<input type="submit" name="btnSupprimer" value="Supprimer">',
                             '<input type="submit" name="btnAnnuler" value="Annuler">',
                         '</td>',
                     '</tr>',
                 '</table>',
              '</form>',

           '</div>
         </div>';
}
 ?>
