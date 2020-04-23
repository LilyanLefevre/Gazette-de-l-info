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
  //construction de la requete
  if(isset($_POST["recherche"])){
    $rec=ll_construire_requete();
    if($rec!=''){
      $tab=ll_resultat_recherche($bd,$rec);
    }
  }

  echo '<main>';
  if($rec==''){
    ll_bar_recherche($erreur);
  }else{
    if(isset($_POST['btnRecherche'])){
      ll_bar_recherche($erreur);
      if($erreur==0){
        ll_aff_section_recherche($tab);
      }
    }else{
      ll_bar_recherche($erreur);
    }
  }

  echo '</main>';

}

//affiche une bar dans une section
function ll_bar_recherche($erreur){
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
  //ll_aff_ligne_input('text', '', 'recherche', $rec, array('placeholder' => '3 caractères minimum', 'required' => 1));
  echo      '<input type="text" name="recherche" placeholder="3 caractères minimum" required>',
            '<input type="submit" name="btnRecherche" value="Rechercher">',
          '</form>',
       '</section>';
}

//fonction qui construit et retourne une requete sql à partir de ce qu'il y
//a dans $_post["recherche"]
function ll_construire_requete(){
  global $erreur;
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


//retourne un tableau d'articles qui correpsondent à la recherche effectuée
function ll_resultat_recherche($bd,$sql){
  global $erreur;
  $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
  $i=0;
  $l=mysqli_num_rows($res);
  if($l==0){
    $erreur=2;
  }
  $tab=array();
  while($i<$l){
    $tab[$i]=mysqli_fetch_assoc($res);
    $i=$i+1;
  }
  return $tab;
}



//affiche tous les articles et les regroupe par leur mois de publication
function ll_aff_section_recherche($tab){
  global $date;
  foreach ($tab as $key => $value) {
    if(ll_determine_date($tab[$key]['arDatePublication'])!=$date){
      if($date!="null"){
        echo '</section>';
      }
      $date=ll_determine_date($tab[$key]['arDatePublication']);
      echo  "<section><h2>",$date,"</h2>";

    }
    ll_aff_article($tab[$key]);
  }
  echo '</section>';
}

//affiche un article à l'intérieur d'une section
function ll_aff_article($art){
  $art=ll_html_proteger_sortie($art);
  echo  '<article class="resume">',
            '<img src="../upload/',$art["arID"],'.jpg" alt="Photo d\'illustration | ',$art['arTitre'],'">',
            '<h3>',$art['arTitre'],'</h3>',
            '<p>',$art['arResume'],'</p>',
            '<footer><a href="../php/article.php?id=',$art["arID"],'">Lire l\'article</a></footer>',
        '</article>';
}

//retourne une date sous la forme: Mois Annee
function ll_determine_date($i){
  global $date;
  $year=(int)($i/100000000);
  $month=(int)(($i%100000000)/1000000);
  $t=ll_get_tableau_mois();
  $month=$t[$month-1];
  return ($month." ".$year);
}


?>
