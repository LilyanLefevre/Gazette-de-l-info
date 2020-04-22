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
                    $droits[0] ? "<li><a href=\"{$prefix}/php/edition.php\">Nouvel article</a></li>" : '',
                    $droits[1] ? "<li><a href=\"{$prefix}/php/admin.php\">Administration</a></li>" : '',
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
    '[b]' => '<span style="font-weight:bold;">','[/b]' => '</span>',
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
    "/\[a:mailto:(.*?)\](.*?)\[\/a\]/i" => "<a href=\"mailto:$1\">$2</a>",
    "/\[a:https:(.*?)\](.*?)\[\/a\]/i" => "<a href=\"https:$1\" title=\"$2\">$2</a>",
    "/\[#(.*?)\]/i" => "&#$1",

  );

  foreach($bbextended as $match=>$replacement){
    $bbtext = preg_replace($match, $replacement, $bbtext);
  }
  return $bbtext;
}



?>
