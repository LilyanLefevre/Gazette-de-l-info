<?php
require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

ll_aff_entete('Recherche', 'Recherche');
$date="null";
$erreur=0;

ll_aff_contenu();

ll_aff_pied();

ob_end_flush();

/**
 * Affichage du contenu principal de la page
 */
function ll_aff_contenu() {
  global $erreur;
  $bd = ll_bd_connecter();
  $rec='';

  //verification si le bouton rechercher est actif
  if(isset($_POST["recherche"])){

    //on construit la requete
    $rec=ll_construire_requete();

    //si la requete n'est pas nulle on l'exécute
    if($rec!=''){
      $tab=ll_resultat_recherche($bd,$rec);
    }
  }

  echo '<main>';

  //si la requete construite était nulle
  //alors on affiche les erreurs que l'utilisateur a commis
  if($rec==''){
    ll_bar_recherche($erreur);
  }else{
    //sinon si le bouton rechercher est actif on affiche la bar de recherche
    //et les résultats
    if(isset($_POST['btnRecherche'])){
      ll_bar_recherche($erreur);
      if($erreur==0){
        ll_aff_section_recherche($tab);
      }
    //si le bouton n'est pas actif on affiche juste la bar de recherche
    }else{
      ll_bar_recherche($erreur);
    }
  }

  echo '</main>';

}


 /**
  * affiche une bar dans une section
  */
function ll_bar_recherche(){
  global $rec,$erreur;
  echo '<section>';
  echo    '<h2>Rechercher des articles</h2>';
  if ($erreur==1){
    echo '<div class="erreur">Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte. <ul>';
    echo '</ul></div>';
  }else{
    if ($erreur==2){
      echo '<div class="erreur">Aucun résultat. <ul>';
      echo '</ul></div>';
    }else{
      echo    '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte.</p>';
    }
  }
  echo      '<form action="recherche.php" method="post" class="recherche">';
  echo      '<input type="text" name="recherche" placeholder="3 caractères minimum" required>',
            '<input type="submit" name="btnRecherche" value="Rechercher">',
          '</form>',
       '</section>';
}

 /**
  * fonction qui construit et retourne une requete sql à partir de ce qu'il y
  * a dans $_post["recherche"]
  *
  * @return String si pas d'erreurs retourne la requete sql, une chaine
  * vide sinon
  */
function ll_construire_requete(){
  global $erreur;

  //si la chaine fait moins de 3 caractères -> erreur=1
  if(strlen($_POST["recherche"])<3){
    $erreur=1;
    return '';
  }

  $rec=htmlspecialchars($_POST["recherche"]);

  //on explose la chaine que l'user a rentrée
  $rec=explode(" ",$rec);
  $sql="SELECT * FROM `article` WHERE ";

  foreach ($rec as $key => $value) {
    //si on est sur un ET on ajoute OR dans la requete
    if($rec[$key]=="ET" || $rec[$key]=="et" ){
      $sql=$sql." OR ";
    }else{
      $sql=$sql."(arTitre LIKE '"."%".$rec[$key]."%' OR arResume LIKE '"."%".$rec[$key]."%')";
      //si la prochaine chaine n'est pas ET alors on met AND
      if(!($key==count($rec)-1) && $rec[$key+1]!="ET"){
          $sql=$sql." AND ";
      }
    }
  }
  $sql=$sql." ORDER BY arDatePublication DESC";
  return $sql;
}


/**
 * fonction qui effectue la recherche d'article

 * @param Object $bd qui est le connecter à la base de données
 * @param String $sql qui est la requete sql à effectuer
 *
 * @return Array les resultats de la requete sql
 * retourne un tableau d'articles qui correpsondent à la recherche effectuée
 */
function ll_resultat_recherche($bd,$sql){
  global $erreur;

  //on lance la requete
  $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

  //on vérifie s'il y a des resultats
  $i=0;
  $l=mysqli_num_rows($res);

  //s'il n'y en a pas -> erreur = 2
  if($l==0){
    $erreur=2;
  }

  //on récpère ligne par ligne les résultats
  $tab=array();
  while($i<$l){
    $tab[$i]=mysqli_fetch_assoc($res);
    $i=$i+1;
  }


  return $tab;
}



/**
  * fonction qui affiche tous les articles et les
  * regroupe par leur mois de publication

  * @param Array $tab tableau issue de la fonction ll_resultat_recherche

 */
function ll_aff_section_recherche($tab){
  global $date;

  //parcours de $tab
  foreach ($tab as $key => $value) {

    //on verifie si la date de l'article est différente de celle de l'article précedent
    if(ll_determine_date($tab[$key]['arDatePublication'])!=$date){

      //si la date n'est pas nulle et qu'elle est différente de l'article précedent
      //on ferme la balise 'section'
      if($date!="null"){
        echo '</section>';
      }

      //on met a jour la date
      $date=ll_determine_date($tab[$key]['arDatePublication']);

      //et on affiche une nouvelle section
      echo  "<section><h2>",$date,"</h2>";

    }
    //enfin, on affiche l'article
    ll_aff_article($tab[$key]);
  }
  //on ferme la derniere balise 'section' ouverte
  echo '</section>';
}

/**
  * affiche un article à l'intérieur d'une section

  * @param Array $tab avec toutes les infos sur un article (son id, son titre, son résumé...)
  */
function ll_aff_article($art){
  $art=ll_html_proteger_sortie($art);
  echo  '<article class="resume">',
            '<img src="../upload/',$art["arID"],'.jpg" alt="Photo d\'illustration | ',$art['arTitre'],'">',
            '<h3>',$art['arTitre'],'</h3>',
            '<p>',$art['arResume'],'</p>',
            '<footer><a href="../php/article.php?id=',$art["arID"],'">Lire l\'article</a></footer>',
        '</article>';
}




?>
