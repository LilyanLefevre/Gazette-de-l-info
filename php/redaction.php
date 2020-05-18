<?php

require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();
$section="null";
ll_aff_entete('L\'Actu', 'L\'Actu');
ll_aff_contenu();

ll_aff_pied();

ob_end_flush();

/**
 * Affichage du contenu principal de la page
 */
function ll_aff_contenu() {
  $bd = ll_bd_connecter();
  echo '<main>',
          '<section>',
              '<h2>Le mot de la rédaction</h2>',
              '<p>Passionnés par le journalisme d\'investigation depuis notre plus jeune âge, nous avons créé en 2019 ce site pour répondre à un
                  réel besoin : celui de fournir une information fiable et précise sur la vie de la
                  <abbr title="Licence Informatique">L-INFO</abbr>
                  de l\'<a href="http://www.univ-fcomte.fr" target="_blank">Université de Franche-Comté</a>.</p>',
              '<p>Découvrez les hommes et les femmes qui composent l\'équipe de choc de la Gazette de L-INFO. </p>',
          '</section>';
  $redac=ll_get_redacteur($bd);
  ll_aff_section($redac);

  echo '</main>';

}

function ll_aff_section($redac){
  global $section;
  foreach ($redac as $key => $value) {
    if($value['utStatut']==1 ||$value['utStatut']==3 ){
      if($section!=ucfirst($value["catLibelle"])){
        if($section!="null"){
          echo '</section>';
        }
        $section=ucfirst($value["catLibelle"]);
        echo "<section><h2>",$section,"</h2>";
      }
      if(!empty($value["reBio"])){
        ll_aff_redacteur($value);
      }
    }
  }
}

//affiche un redacteur en chef
function ll_aff_redacteur_chef($redac){
  $bio=bbcode_to_html($redac['reBio']);
  $img='../upload/'.$redac['rePseudo'].'.jpg';
  if(!file_exists($img)){
    $img="../images/anonyme.jpg";
  }
  echo      '<article class="redacteur" id="',$redac['rePseudo'],'">',
                '<img src=',$img,' width="150" height="200" alt="',$redac['utPrenom'],' ',$redac['utNom'],'">',
                '<h3>',$redac['utPrenom'],' ',$redac['utNom'],'</h3>',
                $bio,
            '</article>';
}

//affiche un redacteur
function ll_aff_redacteur($redac){
  $bio=bbcode_to_html($redac['reBio']);
  $img='../upload/'.$redac['rePseudo'].'.jpg';
  if(!file_exists($img)){
    $img="../images/anonyme.jpg";
  }
  echo      '<article class="redacteur" id="',$redac['rePseudo'],'">',
                '<img src=',$img,' width="150" height="200" alt="',$redac['utPrenom'],' ',$redac['utNom'],'">',
                '<h3>',ucfirst($redac['utPrenom']),' ',ucfirst($redac['utNom']),'</h3>';
  if(!is_null($redac['reFonction'])){
    echo        '<h4>',$redac['reFonction'],'</h4>';
  }
  echo          $bio,
            '</article>';
}



//retourne un tableau associatif avec tous les redacteurs et leur attributs (nom,prenom,bio...)
function ll_get_redacteur($bd){
  $sql="SELECT rePseudo, utPrenom, utNom,utStatut, reBio, reCategorie, reFonction, catLibelle FROM redacteur, utilisateur, categorie WHERE rePseudo=utPseudo AND reCategorie=catID ORDER BY reCategorie ASC";
  $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
  $l=mysqli_num_rows($res);
  $i=0;
  $tab=array();
  while($i<$l){
    $tab[$i]=mysqli_fetch_assoc($res);
    $i=$i+1;
  }
  $tab=ll_html_proteger_sortie($tab);
  return $tab;
}

?>
