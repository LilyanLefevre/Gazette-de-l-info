<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *        à l'application Gazette de L-Info              *
 *********************************************************/


/** Constantes : les paramètres de connexion au serveur MySQL */
define ('BD_NAME', 'gazette_bd');
define ('BD_USER', 'gazette_user');
define ('BD_PASS', 'gazette_pass');
define ('BD_SERVER', 'localhost');


define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 20);

define('LMAX_NOM', 50);
define('LMAX_PRENOM', 60);

define('LMAX_EMAIL', 255);

define('NB_ANNEE_DATE_NAISSANCE', 100);

//_______________________________________________________________
/**
 *  Affichage du début de la page (jusqu'au tag ouvrant de l'élément body)
 *
 *
 *  @param  string  $title      Le titre de la page (<head>)
 *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
 *  @param  array   $css        Le nom de la feuille de style à inclure
 */
function ll_aff_debut($title = '', $prefix='..', $css = 'gazette.css') {

    echo
        '<!doctype html>',
        '<html lang="fr">',
            '<head>',
                '<meta charset="UTF-8">',
                '<title>La gazette de L-INFO', ($title != '') ? ' | ' : '', $title, '</title>',
                $css != '' ? "<link rel='stylesheet' type='text/css' href='{$prefix}/styles/{$css}'>" : '',
            '</head>',
            '<body>';
}



//_______________________________________________________________
/**
 *  Affiche le code du menu de navigation.
 *
 *  @param  string  $pseudo     chaine vide quand l'utilisateur n'est pas authentifié
 *  @param  array   $droits     Droits rédacteur à l'indice 0, et administrateur à l'indice 1
 *  @param  String  $prefix     le préfix du chemin relatif vers la racine du site
 */
function ll_aff_menu($pseudo='', $droits = array(false, false), $prefix = '..') {

    echo '<nav><ul>',
            '<li><a href="', $prefix, '/index.php">Accueil</a></li>',
            '<li><a href="', $prefix, '/php/actus.php">Toute l\'actu</a></li>',
            '<li><a href="', $prefix, '/php/recherche.php">Recherche</a></li>',
            '<li><a href="', $prefix, '/php/redaction.php">La rédac\'</a></li>',
            '<li>';

    // dernier item du menu ("se connecter" ou sous-menu)
    if ($pseudo) {
        echo '<a href="#">', $pseudo, '</a>',
                '<ul>',
                    '<li><a href="', $prefix, '/php/compte.php">Mon profil</a></li>',
                    $droits[0] ? "<li><a href=\"{$prefix}/php/nouveau.php\">Nouvel article</a></li>" : '',
                    $droits[1] ? "<li><a href=\"{$prefix}/php/administration.php\">Administration</a></li>" : '',
                    '<li><a href="', $prefix, '/php/deconnexion.php">Se déconnecter</a></li>',
                '</ul>';
    }
    else {
        echo '<a href="', $prefix, '/php/connexion.php">Se connecter</a>';
    }

    echo '</li></ul></nav>';
}

//_______________________________________________________________
/**
 *  Affichage de l'élément header
 *
 *  @param  string  $h1         Le titre dans le bandeau (<header>)
 *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
 */
function ll_aff_header($h1, $prefix='..'){
    echo '<header>',
            '<img src="', $prefix, '/images/titre.png" alt="La gazette de L-INFO" width="780" height="83">',
            '<h1>', $h1, '</h1>',
        '</header>';
}

//_______________________________________________________________
/**
 *  Affichage du début de la page (de l'élément doctype jusqu'à l'élément header inclus)
 *
 *  Affiche notamment le menu de navigation en utilisant $_SESSION
 *
 *  @param  string  $h1         Le titre dans le bandeau (<header>)
 *  @param  string  $title      Le titre de la page (<head>)
 *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
 *  @param  array   $css        Le nom de la feuille de style à inclure
 *  @global array   $_SESSION
 */
function ll_aff_entete($h1, $title='', $prefix='..', $css = 'gazette.css'){
    ll_aff_debut($title, $prefix, $css);
    $pseudo = '';
    $droits = array(false, false);
    if (isset($_SESSION['user'])){
        $pseudo = $_SESSION['user']['pseudo'];
        $droits = array($_SESSION['user']['redacteur'], $_SESSION['user']['administrateur']);
    }
    ll_aff_menu($pseudo, $droits, $prefix);
    ll_aff_header($h1, $prefix);
}

//_______________________________________________________________
/**
 *  Affichage du pied de page du document.
 */
function ll_aff_pied() {
    echo    '<footer>&copy; Licence Informatique - Janvier 2020 - Tous droits réservés</footer>',
        '</body>',
    '</html>';
}




//_______________________________________________________________
/**
 *  Génère l'URL de l'image d'illustration d'un article en fonction de son ID
 *  - si l'image ou la photo existe dans le répertoire /upload, on renvoie son url
 *  - sinon on renvoie l'url d'une image générique
 *  @param  int     $id         l'identifiant de l'article
 *  @param  String  $prefix     le chemin relatif vers la racine du site
 */
function ll_url_image_illustration($id, $prefix='..') {

    $url = "{$prefix}/upload/{$id}.jpg";

    if (! file_exists($url)) {
        return "{$prefix}/images/none.jpg" ;
    }

    return $url;
}

//_______________________________________________________________
/**
* Vérifie si l'utilisateur est authentifié.
*
* Termine la session et redirige l'utilisateur
* sur la page connexion.php s'il n'est pas authentifié.
*
* @global array   $_SESSION
*/
function ll_verifie_authentification() {
    if (! isset($_SESSION['user'])) {
        ll_session_exit('./connexion.php');
    }
    return true;
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Elle supprime également le cookie de session
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour
 * stocker par exemple l'adresse IP, etc.
 *
 * @param string    URL de la page vers laquelle l'utilisateur est redirigé
 */
function ll_session_exit($page = '../index.php') {
    session_destroy();
    session_unset();
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(),
            '',
            time() - 86400,
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    header("Location: $page");
    exit();
}

//_______________________________________________________________
/**
 *  Calcule le résultat d'une requête SQL et place ceux-ci dans un tableau.
 *  @param  Object  $bd     la connexion à la base de données
 *  @param  String  $sql    la requête SQL à considérer
 */
function ll_bd_select_articles($bd, $sql) {

    // envoi de la requête au serveur de bases de données
    $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

    // tableau de résultat (à remplir)
    $ret = array();

    // parcours des résultats
    while ($t = mysqli_fetch_assoc($res)) {
        $ret[$t['arID']] = $t;
    }

    mysqli_free_result($res);

    return $ret;
}

function bbcode_to_html($bbtext){
  $bbtags = array(
    '[h1]' => '<h1>','[/h1]' => '</h1>',
    '[h2]' => '<h2>','[/h2]' => '</h2>',
    '[h3]' => '<h3>','[/h3]' => '</h3>',

    '[p]' => '<p>','[/p]' => '</p>',

    '[it]' => '<em>','[/it]' => '</em>',
    '[underline]' => '<span style="text-decoration:underline;">','[/underline]' => '</span>',
    '[gras]' => '<span style="font-weight:bold;">','[/gras]' => '</span>',
    '[u]' => '<span style="text-decoration:underline;">','[/u]' => '</span>',
    '[br]' => '<br>',
    '[citation]' => '<blockquote>', '[/citation]' => '</blockquote>',

    '[liste]' => '<ul>','[/liste]' => '</ul>',

    '[ordered_list]' => '<ol>','[/ordered_list]' => '</ol>',
    '[ol]' => '<ol>','[/ol]' => '</ol>',
    '[item]' => '<li>','[/item]' => '</li>',
    '[li]' => '<li>','[/li]' => '</li>',

    '[pre]' => '<pre>','[/pre]' => '</pre>',
  );

  $bbtext = str_ireplace(array_keys($bbtags), array_values($bbtags), $bbtext);

  $bbextended = array(
    "/\[youtube:(.*?):(.*?):(.*?)\s(.*?)\]/i" => "<figure><iframe width=\"$1\" height=\"$2\" src=\"$3\" allowfullscreen></iframe> <figcaption>$4</figcaption></figure>" ,
    "/\[youtube:(.*?):(.*?):(.*?)\s*\]/i" => "<iframe width=\"$1\" height=\"$2\" src=\"$3\" allowfullscreen></iframe>" ,
    "/\[a:mailto:(.*?)\](.*?)\[\/a\]/i" => "<a href=\"mailto:$1\">$2</a>",
    "/\[a:https:(.*?)\](.*?)\[\/a\]/i" => "<a href=\"https:$1\" title=\"$2\">$2</a>",
    "/\[#(.*?)\]/i" => "&#$1",
    "/\[a:#(.*?)](.*?)\[\/a\]/i" => "<a href=\"#$1\">$2</a>",
    );

  foreach($bbextended as $match=>$replacement){
    $bbtext = preg_replace($match, $replacement, $bbtext);
  }
  return $bbtext;
}

//___________________________________________________________________
/**
 * Vérification des champs nom et prénom
 *
 * @param  string       $texte champ à vérifier
 * @param  string       $nom chaîne à ajouter dans celle qui décrit l'erreur
 * @param  array        $erreurs tableau dans lequel les erreurs sont ajoutées
 * @param  int          $long longueur maximale du champ correspondant dans la base de données
 */
function ll_verifier_texte($texte, $nom, &$erreurs, $long = -1){
    mb_regex_encoding ('UTF-8'); //définition de l'encodage des caractères pour les expressions rationnelles multi-octets
    if (empty($texte)){
        $erreurs[] = "$nom ne doit pas être vide.";
    }
    else if(strip_tags($texte) != $texte){
        $erreurs[] = "$nom ne doit pas contenir de tags HTML";
    }
    elseif ($long > 0 && mb_strlen($texte, 'UTF-8') > $long){
        // mb_* -> pour l'UTF-8, voir : https://www.php.net/manual/fr/function.mb-strlen.php
        $erreurs[] = "$nom ne peut pas dépasser $long caractères";
    }
    elseif(!mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $texte)){
        $erreurs[] = "$nom contient des caractères non autorisés";
    }
}


//___________________________________________________________________
/**
 * Vérification des champs texte, resume et titre
 *
 * @param  string       $texte champ à vérifier
 * @param  string       $nom chaîne à ajouter dans celle qui décrit l'erreur
 * @param  array        $erreurs tableau dans lequel les erreurs sont ajoutées
 * @param  int          $long longueur maximale du champ correspondant dans la base de données
 */
function ll_verifier_texte_article($texte, $nom, &$erreurs, $long = -1){
    mb_regex_encoding ('UTF-8'); //définition de l'encodage des caractères pour les expressions rationnelles multi-octets
    if (empty($texte)){
        $erreurs[] = "$nom ne doit pas être vide.";
    }
    else if(strip_tags($texte) != $texte){
        $erreurs[] = "$nom ne doit pas contenir de tags HTML";
    }
    elseif ($long > 0 && mb_strlen($texte, 'UTF-8') > $long){
        // mb_* -> pour l'UTF-8, voir : https://www.php.net/manual/fr/function.mb-strlen.php
        $erreurs[] = "$nom ne peut pas dépasser $long caractères";
    }

}

/**
  * fonction qui créer une date sous la fore: "Mois Annee"
  * pour l'affichage d'une section
  *
  * @return String une chaine sous la forme 'Mois Annee'
  *
  */
function ll_determine_date($i){
  global $date;
  $year=(int)($i/100000000);
  $month=(int)(($i%100000000)/1000000);
  $t=ll_get_tableau_mois();
  $month=$t[$month-1];
  return ($month." ".$year);
}

//_______________________________________________________________
/**
 *  Affchage d'un message d'erreur dans une zone dédiée de la page.
 *  @param  String  $msg    le message d'erreur à afficher.
 */
function ll_aff_erreur($msg) {
    echo '<main>',
            '<section>',
                '<h2>Oups, il y a une erreur...</h2>',
                '<p>La page que vous avez demandée a terminé son exécution avec le message d\'erreur suivant :</p>',
                '<blockquote>', $msg, '</blockquote>',
            '</section>',
        '</main>';
}

/**
  * fonction qui récupère les catégories et leur libellé
  *
  * @param Object $bd connecter à la bd
  *
  * @return Array $tab tableau avec les catégorie et leur libellé
  */
function ll_get_categorie($bd){
  $sql = "SELECT catID, catLibelle FROM categorie";
  $res=mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
  $l=mysqli_num_rows($res);
  $i=0;
  $tab=array();
  while($i<$l){
    $tab[$i]=mysqli_fetch_assoc($res);
    $i=$i+1;
  }

  return $tab;
}
/**
  * fonction qui affiche les erreurs ou le succès du traitement qui a eu lieu
  *
  * @param Array $erreurs, tableau avec des chaines qui contiennent les
  * erreurs commises
  * @param Integer $success, entier qui vaut 1 en cas de succès de la soumission
  * du formulaire et 0 sinon
  */
function ll_aff_erreur_success($erreurs,$success){
  //affichage des éventuelles erreurs commises lors de la soumission du formulaire
  if ($erreurs) {
      echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de vos modifications :<ul>';
      foreach ($erreurs as $err) {
          echo '<li>', $err, '</li>';
      }
      echo '</ul></div>';
  }

  //affichage de l'éventuel succès de l'opération efféctuée
  if($success==1){
    echo '<div class="success">Changement(s) effectué(s) avec succès.<ul>';
    echo '</ul></div>';
  }
}


/**
  * fonction qui verifie le format et l'extension d'une image
  *
  * @param String $chemin le chemin de l'images
  * @param Int $la la ratio de la largeur
  * @param Int $lo le ratio de la longueur
  * @param Int $extension : 1 = GIF, 2 = JPG,3 = PNG, 4 = SWF...
  *
  * @return Boolean vrai si le format et l'extension sont bons
  */
function ll_verif_img($chemin,$la,$lo,$extension){
  $infos_image = @getImageSize($chemin); // info sur la dimension de l'image

  // '@' est placé devant la fonction getImageSize()pour empêcher l'affichage
  // des erreurs si l'image est absente.

   //dimension
   $largeur = $infos_image[0]; // largeur de l'image
   $hauteur = $infos_image[1]; // hauteur de l'image
   $type    = $infos_image[2]; // Type de l'image
  if($type!=$extension){
    return FALSE;
  }

  if((int)($largeur/$la)!=(int)($hauteur/$lo)){
    return FALSE;
  }

  return TRUE;
}
?>
