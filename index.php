<?php

require_once('./php/bibli_gazette.php');
require_once('./php/bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

ll_aff_entete('Le site de désinformation n°1 des étudiants en Licence Info', '', '.');

ll_aff_contenu();

ll_aff_pied();

ob_end_flush();

/**
 * Affichage du contenu principal de la page
 */
function ll_aff_contenu() {

    $bd = ll_bd_connecter();

    echo '<main>';

    // génération des 3 derniers articles publiés
    $sql0 = 'SELECT arID, arTitre FROM article
             ORDER BY arDatePublication DESC
             LIMIT 0, 3';
    $tab0 = ll_bd_select_articles($bd, $sql0);
    ll_aff_vignettes('&Agrave; la Une', $tab0);

    // génération des 3 articles les plus commentés
    $sql1 = 'SELECT arID, arTitre
             FROM article
             LEFT OUTER JOIN commentaire ON coArticle = arID
             GROUP BY arID
             ORDER BY COUNT(coArticle) DESC, rand()
             LIMIT 0, 3';
    $tab1 = ll_bd_select_articles($bd, $sql1);
    ll_aff_vignettes('L\'info brûlante', $tab1);

    // génération des 3 articles parmi les articles restants
    $sql2 = 'SELECT arID, arTitre FROM article
             WHERE arID NOT IN (' . join(',',array_keys($tab0)) . ',' . join(',',array_keys($tab1)) . ')
             ORDER BY rand() LIMIT 0, 3';
    $tab2 = ll_bd_select_articles($bd, $sql2);
    ll_aff_vignettes('Les incontournables', $tab2);

    // affichage de l'horoscope
    ll_aff_horoscope();

    mysqli_close($bd);

    echo '</main>';

}




//_______________________________________________________________
/**
 *  Affichage d'une tableau d'articles sous forme de vignettes.
 *  @param  String  $titre  le titre de la <section>
 *  @param  array   $tab    le tableau des enregistrements à afficher (issus de la table "article")
 */
function ll_aff_vignettes($titre, $tab) {

    echo '<section class="centre"><h2>', $titre, '</h2>';

    foreach ($tab as $value) {
        ll_aff_une_vignette($value);
    }

    echo '</section>';
}


//_______________________________________________________________
/**
 *  Affichage d'un article sous forme de vignette (image + titre de l'article)
 *  @param  array   $value  tableau associatif issu des enregistrements de la table "article"
 */
function ll_aff_une_vignette($value) {

    $value = ll_html_proteger_sortie($value);
    $id = $value['arID'];

    echo    '<a href="./php/article.php?id=', $id, '">',
                '<img src="', ll_url_image_illustration($id, '.'), '" alt="Photo d\'illustration | ', $value['arTitre'], '"><br>',
                $value['arTitre'],
            '</a>';
}



/**
 *  Fonction générant l'horoscope (texte purement statique)
 */
function ll_aff_horoscope() {
    echo
         '<section>',
            '<h2>Horoscope de la semaine</h2>',

            '<p>Vous l\'attendiez tous, voici l\'horoscope du semestre pair de l\'année 2019-2020. Sans surprise, il n\'est pas terrible...</p>',

            '<table id="horoscope">',
                '<tr>',
                    '<td>Signe</td>',
                    '<td>Date</td>',
                    '<td>Votre horoscope</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9800; Bélier</td>',
                    '<td>du 21 mars<br>au 19 avril</td>',
                    '<td rowspan="4">',
                        '<p>Après des vacances bien méritées, l\'année reprend sur les chapeaux de roues. Tous les signes sont concernés. </p>',
                        '<p>Jupiter s\'aligne avec Saturne, péremptoirement à Venus, et nous promet un semestre qui ne sera pas de tout repos. ',
                        'Février sera le mois le plus tranquille puisqu\'il ne comporte que 29 jours.</p>',
                        '<p>Les fins de mois seront douloureuses pour les natifs du 2e décan au moment où tomberont les tant-attendus résultats ',
                            'du module d\'<em>Algorithmique et Structures de Données</em> du semestre 3.</p>',
                    '</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9801; Taureau</td>',
                    '<td>du 20 avril<br>au 20 mai</td>',
                '</tr>',
                '<tr>',
                    '<td>...</td>',
                    '<td>...</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9811; Poisson</td>',
                    '<td>du 20 février<br>au 20 mars</td>',
                '</tr>',
            '</table>',

            '<p>Malgré cela, notre équipe d\'astrologues de choc vous souhaite à tous un bon semestre, et bon courage pour le module de ',
                '<em>Système et Programmation Système</em>.</p>',
        '</section>';

}


?>
