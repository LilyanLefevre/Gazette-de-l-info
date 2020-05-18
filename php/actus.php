<?php

require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

$date="null";

ll_aff_entete('L\'Actu', 'L\'Actu');

ll_aff_contenu();

ll_aff_pied();

ob_end_flush();

/**
 * Affichage du contenu principal de la page
 */
function ll_aff_contenu() {
  $bd = ll_bd_connecter();

  //si la clé id n'est pas présente ou s'il y a d'autres clés on redirige sur /actus.php?id=1
  if(isset($_GET["id"]) && ll_parametres_controle('get',array(),array("id"))){
    if(!ll_est_entier($_GET["id"])){
      //si la clé n'est pas un entier on affiche une erreur
      ll_aff_erreur("L'id de la page doit être un entier.");
    }else{
      echo "<main>";
      ll_aff_section_actus($_GET["id"],$bd);
      ll_aff_page($bd);
      echo '</main>';
    }
  }else{
    echo "<main>";
    ll_aff_section_actus(1,$bd);
    ll_aff_page($bd);
    echo '</main>';
  }
}
/**
  * fonction qui affiche un article avec sa vignette
  *
  * @param Object $bd connecter à la bd
  * @param Integer $id l'id de l'article à afficher
  */
function ll_aff_article_actus($bd,$id){
  $tab=array();
  //on récupère les infos sur l'article à partir de son id
  $tab=ll_bd_select_articles($bd,"SELECT arID, arTitre, arResume FROM article WHERE arID={$id}");
  $tab=ll_html_proteger_sortie($tab);

  //on creer le chemin de l'image de la vignette à afficher
  $imgFile = "../upload/{$id}.jpg";
  if(!file_exists($imgFile)){
    $imgFile="../images/none.jpg";
  }

  //on affiche l'article dans une balise 'article'
  echo  '<article class="resume">',
            '<img src="',$imgFile,'" alt="Photo d\'illustration | ',$tab[$id]['arTitre'],'">',
            '<h3>',$tab[$id]['arTitre'],'</h3>',
            '<p>',$tab[$id]['arResume'],'</p>',
            '<footer><a href="../php/article.php?id=',$id,'">Lire l\'article</a></footer>',
        '</article>';
}

/**
  * fonction qui affiche une section avec 3 articles
  *
  * @param Object $bd connecter à la bd
  * @param Integer $idpage l'id de la page à afficher, sert à calculer les id
  * des articles à afficher
  */
function ll_aff_section_actus($idpage,$bd){
  global $date;
  $tab=ll_id_article_per_date($bd);

  //on calcule l'id maximum de la page atteignable par rapport au nombre d'article
  //présent dans la bd
  $pmax=ceil((count($tab)-1)/4);

  //si l'id passé est supérieur, on va afficher la page d'id maximum
  if($pmax<$idpage){
    $idpage=$pmax;
  }

  //on définit les indices qui définissent l'intervalle des articles à afficher en
  //fonction de la page où on se trouve
  $lastArticle=($idpage*4);
  $firstArticle=$lastArticle-4;
  $lastArticle--;

  //si on a pas 4 articles à afficher alors le dernier article à afficher
  //est celui à la derniere case du tableau
  if($lastArticle>=count($tab)-2){
    $lastArticle=count($tab)-2;
  }

  //on affiche les articles en les regroupant par date
  for($i=$firstArticle;$i<=$lastArticle;$i++){
    if(ll_determine_date($tab[$i][1])!=$date){
      if($date!="null"){
        echo '</section>';
      }
      $date=ll_determine_date($tab[$i][1]);
      echo  "<section><h2>",$date,"</h2>";

    }
    ll_aff_article_actus($bd,$tab[$i][0]);
  }
  echo '</section>';
}


/**
  *retourne les articles dans un tab triés par ordre chronologique
  *
  * @param Object $bd connecter à la bd
  *
  * @return Array $tab
  */
function ll_id_article_per_date($bd){
  //on récupère tous les articles classés dans l'ordre de leur publication
  $res=array();
  $sql = "SELECT arID,arDatePublication
          FROM article
          ORDER BY arDatePublication DESC";
  $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

  //on récupère tous les résultats de la requête
  $i=0;
  while($tab[$i]=mysqli_fetch_row($res)){
    $i=$i+1;
  }

  return $tab;
}


/**
  * affiche les boutons pour voir d'autres articles en bas de la page
  *
  * @param Object $bd connecter à la bd
  *
  */
function ll_aff_page($bd){
  //compte le nombre d'articles
  $sql = "SELECT COUNT(*) as nbArticle FROM article";
  $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
  $tab=mysqli_fetch_assoc($res);

  //on en deduit le nombre de page
  $pmax=ceil($tab["nbArticle"]/4);

  echo "<section> <h2>Pages</h2>";
  $i=1;

  //on affiche les boutons
  while($i<=$pmax){
    echo "<a href=\"actus.php?id=",$i,"\" class=\"pages\">",$i,"  </a>";
    $i++;
  }
  echo "</section>";
}





?>
