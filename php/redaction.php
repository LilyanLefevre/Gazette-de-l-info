<?php

require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

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
  $a=0;
  ll_aff_redacteur($a);

  echo '</main>';

}

function ll_aff_redacteur($redac){
  echo '<section>',
            '<h2>Notre rédacteur en chef</h2>',
            '<article class="redacteur" id="jbigoude">',
                '<img src="../images/johnny.jpg" width="150" height="200" alt="Johnny Bigoude">',
                '<h3>Johnny Bigoude</h3>',
                '<p>Récemment débarqué de la rédaction d\'iTélé suite au scandale Morandini, Johnny insuffle une vision nouvelle et moderne du journalisme au sein de notre rédaction. Leader charismatique et figure incontournable de l\'information en France et à l\'étranger, il est diplômé de la Harvard Business School of Bullshit, promotion 1997.</p>',
                '<p>Véritable puits de sagesse sans fond, Johnny est LA référence dans la rédaction. Présent dans les locaux du département info, il suit au plus près l\'actualité de la Licence, et signe la majorité des articles du journal, en plus d\'en tracer la ligne éditoriale.</p>',
            '</article>',
        '</section>';
}

?>
