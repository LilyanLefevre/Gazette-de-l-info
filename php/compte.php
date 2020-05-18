<?php

require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// Page accessible uniquement aux utilisateurs authentifiés
ll_verifie_authentification();

//verification des clés dans $_POST
ll_p_controle();

$bd = ll_bd_connecter();

$successInfoPerso=0;
$successMdp=0;
$successRedac=0;
$successImg=0;

// si premiere partie soumise, traitement
if (isset($_POST['btnInfoPerso'])) {
    $erreursInfoPerso = ll_traitement_info_perso($bd);
    if(empty($erreursInfoPerso)==true){
      $successInfoPerso=1;
    }
}
else{
    $erreursInfoPerso = FALSE;
}

// si premiere partie soumise, traitement
if (isset($_POST['btnMdp'])) {
    $erreursMdp = ll_traitement_mdp($bd);
    if(empty($erreursMdp)){
      $successMdp=1;
    }
}
else{
    $erreursMdp = FALSE;
}


// si premiere partie soumise, traitement
if (isset($_POST['btnRedac'])) {
    $erreursRedac = ll_traitement_redac($bd);
    if(empty($erreursRedac)){
      $successRedac=1;
    }
}
else{
    $erreursRedac = FALSE;
}

// si premiere partie soumise, traitement
if (isset($_POST['btnImg'])) {
    $erreursImg = ll_traitement_img($bd);
    if(empty($erreursImg)){
      $successImg=1;
    }
}
else{
    $erreursImg = FALSE;
}

ll_aff_entete('Compte', 'Compte');

$user=ll_info_user($bd);
echo '<main>';
ll_aff_formulaire_infos($erreursInfoPerso,$user,$bd);
ll_aff_formulaire_mdp($erreursMdp);
if($_SESSION['user']['redacteur']==true){
  ll_aff_formulaire_redac($bd,$erreursRedac,$user);
  ll_aff_img($erreursImg);
}
echo '</main>';
ll_aff_pied();

ob_end_flush();


//retourne les infos de l'user actuel
function ll_info_user($bd){
  $sql="SELECT * FROM utilisateur,redacteur where utPseudo='{$_SESSION["user"]["pseudo"]}' AND utPseudo=rePseudo";
  $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
  $res=mysqli_fetch_assoc($res);
  return $res;
}

//fonction qui controle qu'il n'y a pas d'autres clé que celles possibles dans
//$_POST
function ll_p_controle(){
  if( !ll_parametres_controle('post', array() , array('nom','prenom','email','naissance_j','naissance_m','naissance_a','radSexe','cbSpam','bio','categorie','fonction','passe1','passe2','btnMdp','btnImg','btnRedac','btnInfoPerso'))) {
      ll_session_exit();
  }
}

/**
 * Contenu de la page : affichage du formulaire de compte
 *
 * En absence de soumission, $erreursInfoPerso est égal à FALSE
 * Quand l'inscription échoue, $erreursInfoPerso est un tableau de chaînes
 *
 *  @param mixed    $erreursInfoPerso
 *  @global array   $_POST
 */
function ll_aff_formulaire_infos($erreursInfoPerso,$user,$bd) {
    global $successInfoPerso;

    $anneeCourante = (int) date('Y');

    // affectation des valeurs à afficher dans les zones du formulaire
    if (isset($_POST['btnInfoPerso'])){
        $nom = ll_html_proteger_sortie(trim($_POST['nom']));
        $prenom = ll_html_proteger_sortie(trim($_POST['prenom']));
        $email = ll_html_proteger_sortie(trim($_POST['email']));
        $jour = (int)$_POST['naissance_j'];
        $mois = (int)$_POST['naissance_m'];
        $annee = (int)$_POST['naissance_a'];
        $civilite = (isset($_POST['radSexe'])) ? (int)$_POST['radSexe'] : 3;
        $mails_pourris = isset($_POST['cbSpam']);
    }else{

        $nom = ll_html_proteger_sortie(trim($user['utNom']));
        $prenom = ll_html_proteger_sortie(trim($user['utPrenom']));
        $email = ll_html_proteger_sortie(trim($user['utEmail']));
        $jour = (int)ll_retourner_jour($user['utDateNaissance']);
        $mois = (int)ll_retourner_mois($user['utDateNaissance']);
        $annee = (int)ll_retourner_annee($user['utDateNaissance']);
        $civilite = $user['utCivilite'];
        $mails_pourris = $user['utMailsPourris'];
    }

    if($civilite=="h"){
      $civilite=1;
    }else{
      $civilite=2;
    }

    /* Des attributs required ont été ajoutés sur tous les champs que l'utilisateur doit obligatoirement remplir */
    echo
        '<section>',
            '<h2>Informations personnelles</h2>',
            '<p>Vous pouvez modifier les informations suivantes.</p>',
            '<form action="compte.php" method="post">';


    if ($erreursInfoPerso) {
        echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de vos modifications :<ul>';
        foreach ($erreursInfoPerso as $err) {
            echo '<li>', $err, '</li>';
        }
        echo '</ul></div>';
    }
    if($successInfoPerso==1){
      echo '<div class="success">Changement(s) effectué(s) avec succès.<ul>';
      echo '</ul></div>';
    }


    echo '<table>';
    ll_aff_ligne_input_radio('Votre civilité :', 'radSexe', array(1 => 'Monsieur', 2 => 'Madame'), $civilite, array('required' => 0));
    ll_aff_ligne_input('text', 'Votre nom :', 'nom', $nom, array('required' => 0));
    ll_aff_ligne_input('text', 'Votre prénom :', 'prenom', $prenom, array('required' => 0));

    ll_aff_ligne_date('Votre date de naissance :', 'naissance', $anneeCourante - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante, $jour, $mois, $annee);

    ll_aff_ligne_input('email', 'Votre email :', 'email', $email, array('required' => 0));
    echo    '<tr>', '<td colspan="2">';
    // l'attribut required est un attribut booléen qui n'a pas de valeur

    $attributs_checkbox = array();
    if ($mails_pourris){
        // l'attribut checked est un attribut booléen qui n'a pas de valeur
        $attributs_checkbox['checked'] = 0;
    }
    ll_aff_input_checkbox('J\'accepte de recevoir des tonnes de mails pourris', 'cbSpam', 1, $attributs_checkbox);

    echo    '</td></tr>',
            '<tr>',
                '<td colspan="2">',
                    '<input type="submit" name="btnInfoPerso" value="Enregistrer">',
                    '<input type="reset" value="Réinitialiser">',
                '</td>',
            '</tr>',
        '</table>',
        '</form>',
        '</section>';
}

//fonction qui affiche le formulaire pour changer de mot de passe
function ll_aff_formulaire_mdp($erreursMdp){
  global $successMdp;
  /* Des attributs required ont été ajoutés sur tous les champs que l'utilisateur doit obligatoirement remplir */
  echo
      '<section>',
          '<h2>Authentification</h2>',
          '<p>Vous pouvez modifier votre mot de passe ci-dessous.</p>',
          '<form action="compte.php" method="post">';


  if ($erreursMdp) {
      echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de votre changement de mot de passe :<ul>';
      foreach ($erreursMdp as $err) {
          echo '<li>', $err, '</li>';
      }
      echo '</ul></div>';

  }
  if($successMdp==1){
    echo '<div class="success">Changement(s) effectué(s) avec succès.<ul>';
    echo '</ul></div>';
  }


  echo '<table>';
  ll_aff_ligne_input('password', 'Choisissez un mot de passe :', 'passe1', '', array('required' => 0));
  ll_aff_ligne_input('password', 'Répétez le mot de passe :', 'passe2', '', array('required' => 0));


  echo    '<tr>', '<td colspan="2">';
  echo    '</td></tr>',
          '<tr>',
              '<td colspan="2">',
                  '<input type="submit" name="btnMdp" value="Enregistrer">',
                  '<input type="reset" value="Réinitialiser">',
              '</td>',
          '</tr>',
      '</table>',
      '</form>',
      '</section>';
}


//fonction qui affiche le formulaire pour changer ses infos de redacteur
function ll_aff_formulaire_redac($bd,$erreursRedac,$user){
  global $successRedac;
  // affectation des valeurs à afficher dans les zones du formulaire
  if (isset($_POST['btnRedac'])){
      $bio = ll_html_proteger_sortie(trim($_POST['bio']));
      $categorie = ll_html_proteger_sortie(trim($_POST['categorie']));
      $fonction = ll_html_proteger_sortie(trim($_POST['fonction']));
  }else{
      $bio = $user['reBio'];
      $categorie = $user['reCategorie'];
      $fonction = $user['reFonction'];
  }

  echo
      '<section>',
          '<h2>Rédaction</h2>',
          '<p>Vous pouvez modifier votre profil de rédacteur ici.</p>',
          '<form action="compte.php" method="post">';


  if ($erreursRedac) {
      echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de vos modifications :<ul>';
      foreach ($erreursRedac as $err) {
          echo '<li>', $err, '</li>';
      }
      echo '</ul></div>';
  }
  if($successRedac){
    echo '<div class="success">Changement(s) effectué(s) avec succès.<ul>';
    echo '</ul></div>';
  }


  echo '<table>';
  echo    '<tr>
            <td class="bio"><label for="bio">Modifier votre biographie:</label></td>
            <td><textarea id="bio" name="bio" rows="10" cols="70"">',$bio,'</textarea></td>';
          '</tr>';

  echo    '<tr>
            <td class="bio"><label for="fonction">Modifier votre fonction:</label></td>
            <td><textarea id="fonction" name="fonction" rows="5" cols="33"">',$fonction,'</textarea></td>';
          '</tr>';
  echo '<tr>', '<td>Votre catégorie :</td>', '<td>';
  ll_aff_liste_categorie($bd,'categorie',$categorie);
  echo '</td>', '</tr>';



  echo    '<tr>', '<td colspan="2">';
  echo    '</td></tr>',
          '<tr>',
              '<td colspan="2">',
                  '<input type="submit" name="btnRedac" value="Enregistrer">',
                  '<input type="reset" value="Réinitialiser">',
              '</td>',
          '</tr>',
      '</table>',
      '</form>',
      '</section>';
}


//fonction qui affiche le formulaire pour changer de photo
function ll_aff_img($erreursImg){
  global $successImg;
  echo
      '<section>',
          '<h2>Photo de profil</h2>',
          '<p>Vous pouvez modifier votre photo de profil ci-dessous.</p>',
          '<form action="compte.php" method="post" enctype="multipart/form-data">';


  if ($erreursImg) {
      echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de votre changement de mot de passe :<ul>';
      foreach ($erreursImg as $err) {
          echo '<li>', $err, '</li>';
      }
      echo '</ul></div>';
  }
  if($successImg){
    echo '<div class="success">Changement(s) effectué(s) avec succès.<ul>';
    echo '</ul></div>';
  }


  echo '<table>';
  echo '<label for="img">Choisir une photo de profil : </label>
        <input type="file" id="img" name="img" accept="image/jpeg">';

  echo    '<tr>', '<td colspan="2">';
  echo    '</td></tr>',
          '<tr>',
              '<td colspan="2">',
                  '<input type="submit" name="btnImg" value="Enregistrer">',
                  '<input type="reset" value="Réinitialiser">',
              '</td>',
          '</tr>',
      '</table>',
      '</form>',
      '</section>';
}

/**
 *  Traitement d'une demande d'inscription.
 *
 *  Si l'inscription réussit, un nouvel enregistrement est ajouté dans la table utilisateur,
 *  la variable de session $_SESSION['user'] est créée et l'utilisateur est redirigé vers la
 *  page index.php
 *
 *  @global array    $_POST
 *  @global array    $_SESSION
 *  @return array    un tableau contenant les erreurs s'il y en a
 */
function ll_traitement_info_perso($bd) {

    $erreursInfoPerso = array();

    // vérification de la civilité
    if (! isset($_POST['radSexe'])){
        $erreursInfoPerso[] = 'Vous devez choisir une civilité.';
    }
    else if (! (ll_est_entier($_POST['radSexe']) && ll_est_entre($_POST['radSexe'], 1, 2))){
        ll_session_exit();
    }

    // vérification des noms et prénoms
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    ll_verifier_texte($nom, 'Le nom', $erreursInfoPerso, LMAX_NOM);
    ll_verifier_texte($prenom, 'Le prénom', $erreursInfoPerso, LMAX_PRENOM);

    // vérification de la date
    if (! (ll_est_entier($_POST['naissance_j']) && ll_est_entre($_POST['naissance_j'], 1, 31))){
        ll_session_exit();
    }

    if (! (ll_est_entier($_POST['naissance_m']) && ll_est_entre($_POST['naissance_m'], 1, 12))){
        ll_session_exit();
    }
    $anneeCourante = (int) date('Y');
    if (! (ll_est_entier($_POST['naissance_a']) && ll_est_entre($_POST['naissance_a'], $anneeCourante  - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante))){
        ll_session_exit();
    }

    $jour = (int)$_POST['naissance_j'];
    $mois = (int)$_POST['naissance_m'];
    $annee = (int)$_POST['naissance_a'];
    if (!checkdate($mois, $jour, $annee)) {
        $erreursInfoPerso[] = 'La date de naissance n\'est pas valide.';
    }
    else if (mktime(0,0,0,$mois,$jour,$annee+18) > time()) {
        $erreursInfoPerso[] = 'Vous devez avoir au moins 18 ans.';
    }

    // vérification du format de l'adresse email
    $email = trim($_POST['email']);
    if (empty($email)){
        $erreursInfoPerso[] = 'L\'adresse mail ne doit pas être vide.';
    }
    else if (mb_strlen($email, 'UTF-8') > LMAX_EMAIL){
        $erreursInfoPerso[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
    }
    // la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
    // est moins forte que celle faite ci-dessous avec la fonction filter_var()
    // Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
    // celle faite ci-dessous
    else if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreursInfoPerso[] = 'L\'adresse mail n\'est pas valide.';
    }

    // vérification si l'utilisateur accepte de recevoir les mails pourris
    if (isset($_POST['cbSpam']) && ! (ll_est_entier($_POST['cbSpam']) && $_POST['cbSpam'] == 1)){
        ll_session_exit();
    }

    // si erreurs --> retour
    if (count($erreursInfoPerso) > 0) {
        return $erreursInfoPerso;   //===> FIN DE LA FONCTION
    }

    // on vérifie si le pseudo et l'adresse mail ne sont pas encore utilisés que si toutes les autres vérifications
    // réussissent car ces 2 dernières vérifications coûtent un bras !

    //verif existence mail
    $emaile = mysqli_real_escape_string($bd, $email);
    $sql = "SELECT utPseudo,utEmail FROM utilisateur WHERE utEmail = '{$emaile}'";
    $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

    while($tab = mysqli_fetch_assoc($res)) {
        if ($tab['utEmail'] == $email && $tab['utPseudo']!=$_SESSION['user']['pseudo'] ){
            $erreursInfoPerso[] = 'Cette adresse email est déjà inscrite.';
        }
    }
    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);

    // si erreurs --> retour
    if (count($erreursInfoPerso) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreursInfoPerso;   //===> FIN DE LA FONCTION
    }

    if ($mois < 10) {
        $mois = '0' . $mois;
    }
    if ($jour < 10) {
        $jour = '0' . $jour;
    }
    $civilite = (int) $_POST['radSexe'];
    $civilite = $civilite == 1 ? 'h' : 'f';

    $mailsPourris = isset($_POST['cbSpam']) ? 1 : 0;

    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd, $prenom);

    $sql = "UPDATE `utilisateur` SET `utNom`='{$nom}',`utPrenom`='{$prenom}',`utEmail`='{$emaile}'
            ,`utDateNaissance`={$annee}{$mois}{$jour},`utCivilite`='$civilite',`utMailsPourris`=$mailsPourris WHERE utPseudo='{$_SESSION['user']["pseudo"]}'";


    mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
}


//fonction qui fait le traitement du formulaire pour changer de mot de passe
function ll_traitement_mdp($bd){
  $erreursMdp=array();
  // vérification des mots de passe
  $passe1 = trim($_POST['passe1']);
  $passe2 = trim($_POST['passe2']);
  if (empty($passe1) || empty($passe2)) {
      $erreursMdp[] = 'Les mots de passe ne doivent pas être vides.';
  }
  else if ($passe1 !== $passe2) {
      $erreursMdp[] = 'Les mots de passe doivent être identiques.';
  }
  // si erreurs --> retour
  if (count($erreursMdp) > 0) {
      return $erreursMdp;   //===> FIN DE LA FONCTION
  }  // calcul du hash du mot de passe pour enregistrement dans la base.
  $passe = password_hash($passe1, PASSWORD_DEFAULT);

  $sql = "UPDATE `utilisateur` SET `utPasse`='{$passe}' WHERE utPseudo='{$_SESSION['user']['pseudo']}'";
  mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
}


//fonction qui fait le traitement du formulaire pour modifier son profil
//de redacteur
function ll_traitement_redac($bd){
  $erreursRedac=array();
  // vérification des champs saisis
  $bio = mysqli_real_escape_string($bd,trim($_POST['bio']));
  $fonction =mysqli_real_escape_string($bd,trim($_POST['fonction']));
  $categorie=(int)$_POST['categorie'];

  if(empty($bio)){
    $erreursRedac[]="La biographie ne peut pas être vide.";
  }

  if(!ll_verif_categorie($bd,$categorie)){
    $erreursRedac[]="La catégorie n'est pas valide.";
  }

  if(empty($fonction)){
    $fonction=NULL;
  }

  // si erreurs --> retour
  if (count($erreursRedac) > 0) {
      return $erreursRedac;   //===> FIN DE LA FONCTION
  }

  $sql = "UPDATE `redacteur` SET `reBio`='{$bio}',`reFonction`='{$fonction}',`reCategorie`='{$categorie}' WHERE rePseudo='{$_SESSION['user']['pseudo']}'";
  mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
}



//fonction qui fait le traitement du formulaire pour changer de photo
function ll_traitement_img($bd){
  $erreursImg=array();

  if (empty($_FILES)) {
      $erreursImg[] = 'La photo doit être au format .jpg.';
  }
  $tmp_name=$_FILES["img"]["tmp_name"];
  $b=move_uploaded_file($tmp_name, "../upload/".$_SESSION['user']['pseudo'].'.jpg');

  if($b==false){
    $erreursImg[]='Echec du téléchargement de l\image.';
  }
  // si erreurs --> retour
  if (count($erreursImg) > 0) {
      return $erreursImg;   //===> FIN DE LA FONCTION
  }
}


//fonction qui retourne le numéro du jour d'une date sous la form aaaammjj
function ll_retourner_jour($date){
  return $date%100;
}

//fonction qui retourne le numéro du mois d'une date sous la form aaaammjj
function ll_retourner_mois($date){
  $inter=(integer)($date/100);
  return $inter%100;
}

//fonction qui retourne le numéro de l'annee d'une date sous la form aaaammjj
function ll_retourner_annee($date){
  return (integer)($date/10000);
}

//___________________________________________________________________
/**
 * Affiche une liste déroulante représentant les categories de redacteur
 *
 * @param string    $nom       Le nom de la liste déroulante (valeur de l'attribut name)
 * @param int       $default   La catégorie qui doit être sélectionnée par défaut
 */
function ll_aff_liste_categorie($bd,$nom, $defaut) {
    $cat = ll_get_categorie($bd);
    //on met ce tableau sous la forme: catID => catLibelle
    $tab=array();
    foreach ($cat as $key => $value) {
      $tab[$value['catID']]=$value['catLibelle'];
    }

    ll_aff_liste($nom, $tab, $defaut);
}

function ll_verif_categorie($bd,$id){
    $sql = "SELECT * FROM categorie WHERE catID='{$id}'";
    $res=mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

    if(mysqli_num_rows($res)!=0){
      return true;
    }
    return false;
}
?>
