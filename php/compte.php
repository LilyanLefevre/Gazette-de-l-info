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

//variables globales qui valent 1 en cas de succes
$successInfoPerso=0;
$successMdp=0;
$successRedac=0;
$successImg=0;

// si premier formulaire soumis, traitement
if (isset($_POST['btnInfoPerso'])) {
    $erreursInfoPerso = ll_traitement_info_perso($bd);
    if(empty($erreursInfoPerso)==true){
      $successInfoPerso=1;
    }
}
else{
    $erreursInfoPerso = FALSE;
}

// si deuxieme formulaire soumis, traitement
if (isset($_POST['btnMdp'])) {
    $erreursMdp = ll_traitement_mdp($bd);
    if(empty($erreursMdp)){
      $successMdp=1;
    }
}
else{
    $erreursMdp = FALSE;
}


// si troisieme formulaire soumis, traitement
if (isset($_POST['btnRedac'])) {
    $erreursRedac = ll_traitement_redac($bd);
    if(empty($erreursRedac)){
      $successRedac=1;
    }
}
else{
    $erreursRedac = FALSE;
}

// si quatrieme formulaire soumis, traitement
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


/**
  * retourne les infos de l'utilisateur actuel
  *
  * @param Object $bd connecter à la bd
  *
  * @return Array $res, tableau associatif avec les infos de l'utilisateur
  */
function ll_info_user($bd){

  if($_SESSION['user']['redacteur']==true){
    $sql="SELECT * FROM utilisateur,redacteur where utPseudo='{$_SESSION["user"]["pseudo"]}' AND utPseudo=rePseudo";
  }else{
    $sql="SELECT * FROM utilisateur where utPseudo='{$_SESSION["user"]["pseudo"]}' ";
  }
  $res = mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
  $res=mysqli_fetch_assoc($res);
  return $res;
}

/**
  * fonction qui controle qu'il n'y a pas d'autres clé que celles possibles dans
  * $_POST
  *
  */
function ll_p_controle(){
  if( !ll_parametres_controle('post', array() , array('nom','prenom','email','naissance_j','naissance_m','naissance_a','radSexe','cbSpam','bio','categorie','fonction','passe1','passe2','btnMdp','btnImg','btnRedac','btnInfoPerso'))) {
      ll_session_exit();
  }
}

/**
 * Contenu de la page : affichage du formulaire de modification du compte
 *
 * En absence de soumission, $erreursInfoPerso est égal à FALSE
 * Quand la modification échoue, $erreursInfoPerso est un tableau de chaînes
 *
 *  @param mixed    $erreursInfoPerso
 *  @global array   $_POST
 *  @param Object   $bd, connecter à la bd
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

    echo
        '<section>',
            '<h2>Informations personnelles</h2>',
            '<p>Vous pouvez modifier les informations suivantes.</p>',
            '<form action="compte.php" method="post">';

    ll_aff_erreur_success($erreursInfoPerso,$successInfoPerso);

    //affichage des champs
    echo '<table>';
    ll_aff_ligne_input_radio('Votre civilité :', 'radSexe', array(1 => 'Monsieur', 2 => 'Madame'), $civilite, array('required' => 0));
    ll_aff_ligne_input('text', 'Votre nom :', 'nom', $nom, array('required' => 0));
    ll_aff_ligne_input('text', 'Votre prénom :', 'prenom', $prenom, array('required' => 0));

    ll_aff_ligne_date('Votre date de naissance :', 'naissance', $anneeCourante - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante, $jour, $mois, $annee);

    ll_aff_ligne_input('email', 'Votre email :', 'email', $email, array('required' => 0));
    echo    '<tr>', '<td colspan="2">';

    $attributs_checkbox = array();
    if ($mails_pourris){
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

/**
 * Contenu de la page : affichage du formulaire de modification du mdp
 *
 * En absence de soumission, $erreursMdp est égal à FALSE
 * Quand la modification échoue, $erreursMdp est un tableau de chaînes
 *
 *  @param mixed    $erreursMdp
 */
function ll_aff_formulaire_mdp($erreursMdp){
  global $successMdp;
  echo
      '<section>',
          '<h2>Authentification</h2>',
          '<p>Vous pouvez modifier votre mot de passe ci-dessous.</p>',
          '<form action="compte.php" method="post">';

  ll_aff_erreur_success($erreursMdp,$successMdp);

  //affichage des champs
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


/**
 * Contenu de la page : affichage du formulaire de modification des Informations
 * de rédacteur
 *
 * En absence de soumission, $erreursRedac est égal à FALSE
 * Quand la modification échoue, $erreursRedac est un tableau de chaînes
 *
 *  @param mixed    $erreursRedac
 *  @global array   $_POST
 *  @param Object   $bd, connecter à la bd
 */
 function ll_aff_formulaire_redac($bd,$erreursRedac,$user){
  global $successRedac;

  // affectation des valeurs à afficher dans les zones du formulaire
  if (isset($_POST['btnRedac'])){
      $bio = trim($_POST['bio']);
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

  ll_aff_erreur_success($erreursRedac,$successRedac);

  //affichage du formulaire
  echo '<table>';
  echo    '<tr>',
            '<td class="bio"><label for="bio">Modifier votre biographie:</label></td>',
            '<td><textarea id="bio" name="bio" rows="10" cols="70">',$bio,'</textarea></td>';
          '</tr>';

  echo    '<tr>',
            '<td class="bio"><label for="fonction">Modifier votre fonction:</label></td>',
            '<td><textarea id="fonction" name="fonction" rows="5" cols="33">',$fonction,'</textarea></td>';
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


/**
 * Contenu de la page : affichage du formulaire de modification de la photo de
 * profil
 *
 * En absence de soumission, $erreursImg est égal à FALSE
 * Quand la modification échoue, $erreursImg est un tableau de chaînes
 *
 *  @param mixed    $erreursImg
 */
 function ll_aff_img($erreursImg){
  global $successImg;
  echo
      '<section>',
          '<h2>Photo de profil</h2>',
          '<p>Vous pouvez modifier votre photo de profil ci-dessous.</p>',
          '<form action="compte.php" method="post" enctype="multipart/form-data">';


  ll_aff_erreur_success($erreursImg,$successImg);


  echo '<table>';
  echo '<tr>',
         '<td><label for="img">Choisir une photo de profil :</label></td>',
         '<td><input type="file" id="img" name="img" accept="image/jpeg"></td>',
        '</tr>';

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
 * Traitement d'une demande de modification des infos. personnelles
 * Si la modification échoue, $erreursInfoPerso est un tableau de chaînes
 *
 *  @param Object $bd connecter à la bd
 *
 *  @return array un tableau contenant les erreurs s'il y en a
 */
function ll_traitement_info_perso($bd) {

    $erreursInfoPerso = array();

    if( !ll_parametres_controle('post', array('btnInfoPerso','nom','prenom','naissance_a','naissance_j','naissance_m','email') , array('radSexe','cbSpam'))) {
       ll_session_exit();
    }

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

    //on met un 0 devant le mois s'il est <10
    if ($mois < 10) {
        $mois = '0' . $mois;
    }

    //on met un 0 devant le jour s'il est <10
    if ($jour < 10) {
        $jour = '0' . $jour;
    }

    //on convertit la civilité en lettre
    $civilite = (int) $_POST['radSexe'];
    $civilite = $civilite == 1 ? 'h' : 'f';

    $mailsPourris = isset($_POST['cbSpam']) ? 1 : 0;

    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd, $prenom);

    //on construit la requête et on l'exécute
    $sql = "UPDATE `utilisateur` SET `utNom`='{$nom}',`utPrenom`='{$prenom}',`utEmail`='{$emaile}'
            ,`utDateNaissance`={$annee}{$mois}{$jour},`utCivilite`='$civilite',`utMailsPourris`=$mailsPourris WHERE utPseudo='{$_SESSION['user']["pseudo"]}'";
    mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);
}


/**
 * Traitement d'une demande de modification de mot de passe
 * Si la modification échoue, $erreursMdp est un tableau de chaînes
 *
 *  @param Object $bd connecter à la bd
 *
 *  @return array un tableau contenant les erreurs s'il y en a
 */
 function ll_traitement_mdp($bd){
  $erreursMdp=array();
  if( !ll_parametres_controle('post', array('passe1','passe2','btnMdp') , array())) {
     ll_session_exit();
  }
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


/**
 * Traitement d'une demande de modification du profil rédacteur
 * Si la modification échoue, $erreurrRedac est un tableau de chaînes
 *
 *  @param Object $bd connecter à la bd
 *
 *  @return array un tableau contenant les erreurs s'il y en a
 */
function ll_traitement_redac($bd){
  $erreursRedac=array();
  if( !ll_parametres_controle('post', array('btnRedac','bio','fonction','categorie') , array())) {
     ll_session_exit();
  }

  // vérification des champs saisis
  $bio = mysqli_real_escape_string($bd,trim($_POST['bio']));
  $fonction =mysqli_real_escape_string($bd,trim($_POST['fonction']));
  $categorie=(int)$_POST['categorie'];

  //on verifie la bio
  ll_verifier_texte_article($bio, 'La biographie', $erreursRedac);

  //on vérifie que la catégorie existe
  if(!ll_verif_categorie($bd,$categorie)){
    $erreursRedac[]="La catégorie n'est pas valide.";
  }

  //si la fonction est vide alors on lui attribut la valeur NULL pour la requete sql
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



/**
 * Traitement d'une demande de modification d'image
 * Si la modification échoue, $erreursImg est un tableau de chaînes
 *
 *  @param Object $bd connecter à la bd
 *
 *  @return array un tableau contenant les erreurs s'il y en a
 */
function ll_traitement_img($bd){
  $erreursImg=array();

  if( !ll_parametres_controle('post', array('btnImg') , array())) {
     ll_session_exit();
  }

  //on vérifie la présence d'un fichier
  if (empty($_FILES['img']['name'])) {
      $erreursImg[] = 'Il doit y avoir une image.';
  }

  if($_FILES['img']['size']>1000000){
    $erreursImg[] = 'La photo doit faire 1Mo maximum.';
  }

  //2 correspond au format JPG
  if(ll_verif_img($_FILES['img']['tmp_name'],3,4,2)==FALSE){
    $erreursImg[] = 'La photo doit être au format .jpg et être au format 3:4.';
  }

  $tmp_name=$_FILES["img"]["tmp_name"];

  // si erreurs --> retour
  if (count($erreursImg) > 0) {
      return $erreursImg;   //===> FIN DE LA FONCTION
  }

  //on déplace le fichier dans ../upload/
  $b=move_uploaded_file($tmp_name, "../upload/".$_SESSION['user']['pseudo'].'.jpg');

  //on vérifie que le fichier ait bien été téléchargé sur le serveur
  if($b==false){
    $erreursImg[]='Echec du téléchargement de l\'image.';
  }

  // si erreurs --> retour
  if (count($erreursImg) > 0) {
      return $erreursImg;   //===> FIN DE LA FONCTION
  }
}


/**
 * fonction qui retourne le numéro du jour d'une date sous la forme aaaammjj
 *
 * @param Integer $date la date à convertir
 *
 * @return Integer le nuémro du mois
 */
 function ll_retourner_jour($date){
  return $date%100;
}

/**
 * fonction qui retourne le numéro du mois d'une date sous la forme aaaammjj
 *
 * @param Integer $date la date à convertir
 *
 * @return Integer le nuémro du jour
 */
function ll_retourner_mois($date){
  $inter=(integer)($date/100);
  return $inter%100;
}

/**
 * fonction qui retourne le numéro de l'année d'une date sous la forme aaaammjj
 *
 * @param Integer $date la date à convertir
 *
 * @return Integer le nuémro de l'année
 */
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

/**
  * fonction qui vérifie que la catégorie donnée existe bien dans la table categorie
  *
  * @param Object $bd connecter à la bd
  * @param Integer $id le numéro de la catégorie
  *
  * @return Boolean
  */
function ll_verif_categorie($bd,$id){
    $sql = "SELECT * FROM categorie WHERE catID='{$id}'";
    $res=mysqli_query($bd, $sql) or ll_bd_erreur($bd, $sql);

    if(mysqli_num_rows($res)!=0){
      return true;
    }
    return false;
}
?>
