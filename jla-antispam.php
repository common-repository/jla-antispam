<?php
/**
 * Plugin Name: JLAAntispam
 * Plugin URI:
 * Description: Antipam maison pour les formulaires Contact Form 7
 * Version: 1.0
 * Author: Julien LARRART - Zapoa Multimédia
 * Author URI: www.julien-larrart.net
 * License:
 */ 
defined('ABSPATH') or die("No script kiddies please!");

/*
	! FUTURES EVOLUTIONS
		- configuration des temps en admin
		- visualisation des graph en admin
	! IMPORTANT
	* MOTS BANNIS
		- Dans le cas où le dictionaire ne serait pas assez complet, il serait pertinent d'utiliser une méthode approximant 2 mots.
		* Ref : https://docstore.mik.ua/orelly/webprog/php/ch04_06.htm#:~:text=PHP%20provides%20several%20functions%20that,()%2C%20and%20levenshtein(%20).&text=The%20similar_text(%20)%20function%20returns%20the,string%20arguments%20have%20in%20common.
	! RAPPEL :
	* LES FILTRES
		-	$activated_bannedword 			= 	Gestion des mots bannis ; stockés dans banned_words.csv ( Possibilités d'améliorer la granularité sur certains mots );
			-	$deactivated_cats 			= 	Possibilité de désactiver les catégories de mots bannis
			-	$custom_banned_words 		= 	Possibilité d'ajouter des mots bannis
		-	$activated_refere 				=  	On vérifie que le referrer est cohérent.
		-	$activated_nolink				=	Activer / désactiver la possibilité de poster des liens
		-	$activated_twinHoneypotTestJS 	= 	Vérification des champs jumeaux du honeypot
		-	$activated_timeBeforeFirstSend 	= 	Temps minimum avant l'envoie du premier message
		-	$activated_emailSendingTimeout	=	Temps minimum entre chaque envoi de mail
*/

 /*** CONFIGURATION ***/ 
define("NOT_EXISTS", "not_exists"); // retour de valeur de get_option() dans le cas où l'option n'existe pas
$GLOBALS['jlaantispam_dossier_token'] = str_replace('\\', '/', __DIR__ . "/users_tokens/");
$GLOBALS['jlaantispam_uploads_data'] = wp_upload_dir()["basedir"]."/jla_antispam/";
$GLOBALS['jlaantispam_dossier_exportLogs'] = "logs";
$GLOBALS['jlaantispam_HTTP_REFERER'] = null;


if(! $GLOBALS['jlaantispam_HTTP_REFERER'] = wp_get_referer()){ // récupère la valeur du referer
	if( isset($_SERVER['jlaantispam_HTTP_REFERER']) ){
		$GLOBALS['jlaantispam_HTTP_REFERER'] = sanitize_text_field($_SERVER['jlaantispam_HTTP_REFERER']);
	}
}

	// Filtres
$GLOBALS['filters'] = [
	/* Example filtre
	$var_filtre_ = array(
		"titre" => "Le titre intelligible du plugin. Une sorte de description en somme. ",
		"id"	=> "jlaantispam_filter_id_de_mon_filtre__attention_a_bien_le_prefixer",
	 	"value" => // true/false | array(), // => si array() (ou autre valeur), il faudra bien définir l'affichage et le comportement souhaité. True/False fonctionne tout seul
		"data"	=> array("info1" => "valeur1", "info2" true), // un tableau permettant l'ajout de données libres, complémentaire à value
		"parentof" => ["jlaantispam_filter_deactivated_cats", "jlaantispam_filter_custom_banned_words"] 
				/*  NOTES SUR "parentof" : Une liste d'ID de filtre enfant d'un filtre True/False. 
					Permet à l'[dés]activation de désactiver en admin les filtres (empêche l'input). 
					Attention, au niveau du hook d'envoie de message, dans les conditions de filtres, 
					à tout de même mettre le parent en condition de test. "parentof" n'est que pour le visuel de l'admin. 
				/* 	Et les enfants dopivent suivre le parent dans la liste des $GLOBALS['filters']
	),
	*/
	$activated_bannedword = array(
		"titre" => "Activer les mots bannis",
		"id"	=> "jlaantispam_filter_activated_bannedword",
		"value" => true,
		"parentof" => ["jlaantispam_filter_deactivated_cats", "jlaantispam_filter_custom_banned_words"]
	),
	$deactivated_cats = array(
		"titre" => "Catégories",
		"id"	=> "jlaantispam_filter_deactivated_cats",
		"value" => [], //["drug", "porn", etc.];
	),
	$custom_banned_words = array(
		"titre" => "Mot bannis personnalisés",
		"id"	=> "jlaantispam_filter_custom_banned_words",
		"value" => [], //["zut"]
	),
	$activated_refere = array(
		"titre" => "Referrer activé",
		"id"	=> "jlaantispam_filter_activated_refere",
		"value" => true,
	),
	$activated_nolink = array(
		"titre" => "Suppression des lien dans le message",
		"id"	=> "jlaantispam_filter_activated_nolink",
		"value" => true,
	),
	$activated_noemail = array(
		"titre" => "Suppression des e-mails dans le message",
		"id"	=> "jlaantispam_filter_activated_noemail",
		"value" => true,
	),
	$activated_twinHoneypotTestJS = array(
		"titre" => "Vérification des valeurs similaires dans les honeypots jumeaux",
		"id"	=> "jlaantispam_filter_activated_twinHoneypotTestJS",
		"value" => true,
	),
	$activated_emptyHoneypot = array(
		"titre" => "Vérification de la valeur (vide) du second honeypot des honeypots jumeaux",
		"id"	=> "jlaantispam_filter_activated_emptyHoneypot",
		"value" => true,
	),
	$activated_timeBeforeFirstSend = array(
		"titre" => "Délai minimum avant l'envoi du premier nouveau message",
		"id"	=> "jlaantispam_filter_activated_timeBeforeFirstSend",
		"value" => true,
		"data"	=> array(
			"time" => 10, // avant de pourvoir envoyer un message | Temps moyen-rapide : ~1m ; Temps minimum humain : ~7s
		),
	),
	$activated_emailSendingTimeout = array(
		"titre" => "Délai minimum avant l'envoi d'un nouveau message",
		"id"	=> "jlaantispam_filter_activated_emailSendingTimeout",
		"value" => true,
		"data"	=> array(
			"time" => 10, // avant de pourvoir renvoyer un message 
		),
	),
];


// Chargement des traductions
add_action( 'init', 'jlaantispam_load_utils', 1 ); 
function jlaantispam_load_utils() {
	load_plugin_textdomain('jlaantispam', false, dirname(plugin_basename( __FILE__ ) ) . '/languages' );
}

// Chargement des fichiers externes (formulaire)
add_action('wp_enqueue_scripts','jlaantispam_scripts');
function jlaantispam_scripts() {
	wp_enqueue_script( 'jlaantispam_js', plugins_url('script.js', __FILE__), false, true);
	wp_enqueue_style( 'jlaantispam_css', plugins_url('style.css', __FILE__), false, false, 'screen');
}

// Chargement des fichiers externes (admin)
add_action('admin_init','jlaantispam_scripts_admin');
function jlaantispam_scripts_admin() {
	wp_enqueue_script( 'jlaantispam_js', plugins_url('script_admin.js', __FILE__), false, true);
	wp_enqueue_style( 'jlaantispam_css', plugins_url('style_admin.css', __FILE__), false, false, 'screen');
}

// Création de la page de configuration en admin
add_action('admin_menu', 'jlaantispam_admin_menu');
function jlaantispam_admin_menu(){
	$imgUrl = wp_normalize_path(plugin_dir_url( __FILE__ ) . "ressources/logo.svg");
	$text = __('Anti Spam Maison', 'jlaantispam');
	add_menu_page($text, $text,'edit_posts' , 'jlaantispam_config_main', 'jlaantispam_config_page', $imgUrl);
}

function jlaantispam_config_page(){
	if(isset($_POST['submit'])){
		// on vérifie que le token de sécurité est valide
		if(!wp_verify_nonce($_POST['jlaantispam_nonce'],'jlaantispam_nonce')){
			die('Token non valide');
		}

		// mise à jour du contenu en BD
		foreach ($GLOBALS['filters'] as $filter) {
			$sane_post = sanitize_post( $_POST );
			$value = array_key_exists($filter["id"], $sane_post) ? 
				$sane_post[$filter["id"]] 
				: ( is_array($filter["value"]) ? array() : "false" );
			update_option( $filter["id"],  $value);
		};

	}
		?>
		<div class="wrap theme-options-page">
			<h2><?php _e('Configuration de l\'AntiSpam Maison','jlaantispam'); ?></h2>
			<hr>
			<form method="post" action="">
				<input type="hidden" name="jlaantispam_nonce" value="<?php echo wp_create_nonce('jlaantispam_nonce') ?>"/>
				
				<?php submit_button("Mettre à jour les informations"); ?>

				<table class="form-table">
						<?php 
							
						foreach ($GLOBALS['filters'] as $filter) {
							
							?><tr valign="top" <?php if(isset($filter["parentof"])) esc_attr_e('data-parentof="'.implode(',',$filter["parentof"]).',"'); ?> id="<?php esc_attr_e($filter["id"]) ?>"><?php
							
								$filter_data_value = get_option( $filter["id"], NOT_EXISTS ); // si l'option n'existe pas, get_options() retourne NOT_EXISTS
								if ($filter_data_value == NOT_EXISTS) {
									add_option($filter["id"], $filter["value"]);
									$filter_data_value = get_option( $filter["id"], NOT_EXISTS );
								}

								?>
									<th scope="row"><span><?php _e($filter["titre"],'jlaantispam') ?></span></th>
									<td>
										<?php
										// ddd($filter_data_value);
										if(is_array($filter_data_value)){
											switch ($filter["id"]) {
												case "jlaantispam_filter_custom_banned_words":
													?>
													<div class="arrayOption">
														<input type="text">
														<input type="button" class="btnAddWordToFollowingList" value="Ajouter au dictionnaire">
														<ul data-name="<?php esc_attr_e($filter["id"])?>[]" class="addedWords wordList">
															<?php if(! empty($filter_data_value)): ?>
																<?php 
																// tous les mots customs (même ceux notés en dur dans le code) sont enregistrés en BD, je me dis que ça permet de tout centraliser. Du coups, il les différencier via l'array_diff() pour l'affichage en admin
																$saved_banned_words = array_diff( $filter_data_value, $GLOBALS["custom_banned_words"]["value"] ); 
																	foreach ($saved_banned_words as $banned_word) {
																		?>
																			<li class="jlaantispam_word">
																				<div class="outputDeleter"></div>
																				<output><?php esc_html_e($banned_word) ?></output>
																				<input class="jlaantispam_hidden" name="<?php esc_attr_e($filter["id"]) ?>[]" value="<?php esc_attr_e($banned_word) ?>">
																			</li>
																		<?php
																	}
																	foreach ($GLOBALS["custom_banned_words"]["value"] as $banned_word) {
																		?>
																			<li class="jlaantispam_word noOutputDeleter">
																				<output><?php esc_html_e($banned_word) ?></output>
																				<input class="jlaantispam_hidden" name="<?php esc_attr_e($filter["id"]) ?>[]" value="<?php esc_attr_e($banned_word) ?>">
																			</li>
																		<?php
																	}
																?>
															<?php endif; ?>
														</ul>
													</div>
													<?php										
												break;

												case "jlaantispam_filter_deactivated_cats":
													?>
													<ul id="deactivatedCats" class="alreadyExistingWords wordList">
														<?php 
														$cats = jlaantispam_getBannedWordsCats();
														foreach ($cats as $cat):
															
															$isForcedDisabled = in_array($cat["name"], $GLOBALS['deactivated_cats']["value"]); ?>
															<li class="jlaantispam_word">
																<label class="<?php if($isForcedDisabled) echo "disabledInputChild" ?> switch admin">
																	<input class="jlaantispam_hidden" value="<?php esc_attr_e($cat["name"]) ?>" name="<?php esc_attr_e($filter["id"])?>[]" type="checkbox" <?php if($isForcedDisabled) echo "disabled" ?> <?php if($cat["state"]) echo "checked"?>>
																	<span class="slider round"></span>
																</label>
																<output><?php esc_html_e($cat["name"]) ?></output>
															</li>
														<?php endforeach; ?>
													</ul>
													<?php
												break;
												
												default:break;
											}
										}
										//else if(is_new_value_type()){}
										else{ // dans le cas d'un switch true/false
										?> 
											<label class="switch admin">
												<input class="jlaantispam_hidden" value="true" name="<?php esc_attr_e($filter["id"])?>" type="checkbox" <?php if($filter_data_value === "true") echo "checked"?>>
												<span class="slider round"></span>
											</label>
										<?php
										}
									?></td>
							</tr><?php
							}
						?>
				</table>
				<?php submit_button("Mettre à jour les informations"); ?>
			</form>
		</div>
		<?php
}

// ajoute les champs filou au formulaire
add_filter( 'wpcf7_form_elements', 'jlaantispam_addFilousFields'); 
function jlaantispam_addFilousFields($e) {
	$e .= 
	'<p>
		<input aria-label="Ne pas remplir !" type="text" class="filou" name="price1" id="price1" value="filou" autocomplete="off" />
		<input aria-label="Ne pas remplir !" type="text" class="filou" name="price2" id="price2" value="" autocomplete="off" />
		<input aria-label="Ne pas remplir !" type="email" class="filou" name="mail_bis" value="" autocomplete="off" />
		<input type="jlaantispam_hidden" class="filou" name="temps"  value=' . time() . ' readonly /> 
	</p>';
    return $e;
}

//on empêche l'enregistrement des "faux" champs dans la bdd
add_filter('cfdb_form_data', 'jlaantispam_remove_field_before_cf7db_save');
function jlaantispam_remove_field_before_cf7db_save($formData){
	$fieldsToRemove = array('price1', 'price2', 'mail_bis', 'temps');
	foreach ($fieldsToRemove as $fieldName) {
		unset($formData->posted_data[$fieldName]);
	}
    return $formData;
}

//nettoyage des vieux tokens
add_action('wpcf7_mail_sent', 'jlaantipam_tokensCleanup');
function jlaantipam_tokensCleanup() { 
	$max = $GLOBALS['activated_emailSendingTimeout']["data"]["time"] + 10; // on ajoute 10 comme marge de sécurité
	wp_mkdir_p( $GLOBALS['jlaantispam_dossier_token'] );
    $stored_user_token_files = new FilesystemIterator($GLOBALS['jlaantispam_dossier_token']);

	foreach ($stored_user_token_files as $stored_user_token_file) { // pour chaque fichier, on vérifie s'il n'est pas trop vieux
		$stored_user_timestamp = jlaantispam_getTimestamp( $stored_user_token_file->getFilename() );
		if ( $stored_user_timestamp < (time() - $max) ){
			unlink($GLOBALS['jlaantispam_dossier_token'] . $stored_user_token_file->getFilename());
		}
	}
}

// add_action('wpcf7_before_send_mail', 'jlaantispam_mainFilters',9);
// function jlaantispam_mainFilters($wpcf7_data) {
add_action('wpcf7_spam', 'jlaantispam_mainFilters',9);
function jlaantispam_mainFilters($spam) {
	if ( $spam ) {
		return $spam;
	};

	session_start();
	$token = jlaantispam_creationDuToken(session_id());

	// FILTRES

	//on dégage les messages contenants un mot bannis
	/* sources : https://studiocotton.co.uk/blog/websites/seo/ban-wordpress-words-for-spam/ 
				...
	*/
	if ( (	$CBW = get_option( $GLOBALS['activated_bannedword']["id"], NOT_EXISTS )
			AND $CBW !==  'false' ) 
		AND isset($_REQUEST['message'])) {
		//ouverture du fichier
		if (($handle = fopen(str_replace('\\', '/', __DIR__ . "/banned_words.csv"), "r")) !== false) {

			// initialisation des catégories de mots à traiter
			$cats = jlaantispam_getBannedWordsCats();
			if($cats !== false){
				$deactivated_cats = array_filter($cats, function($cat){return $cat["state"];});
				$deactivated_cats_positions = array_keys($deactivated_cats);
			}

			// parcours des mots + garde en cas de mot interdit trouvé
			$bannedWords = get_option( $GLOBALS['custom_banned_words']["id"], NOT_EXISTS ); // si l'option n'existe pas, get_options() retournera NOT_EXISTS
			if ($filter_data_value !== NOT_EXISTS) {

				fgetcsv($handle); // permet de skip la première ligne, les catégories. Sinon, elles sont considérées comme des mots du dictionnaire
				while(($bannedWordsLine = fgetcsv($handle, 0, ";")) !== false) {
					foreach($deactivated_cats_positions as $deactivated_cat_position) {
						unset($bannedWordsLine[$deactivated_cat_position]); // on vide les mots qui appartiennent aux catégories désactivées
					}

					foreach ($bannedWordsLine as $bannedWord) {
						if($bannedWord !== "") $bannedWords[] = $bannedWord;
					}
				}
				
				foreach ($bannedWords as $bannedWord) {
					if ( stripos($_REQUEST['message'],$bannedWord) !== false ){
						$spam = true;
						$IDFiltreSpam = 1;
						$erreurFiltreSpam = 'Vous n\'êtes pas autorisé à poster un message contenant un mot banni. Mot incriminé : "' . $bannedWord . '". Veuillez remplacer ce mot par un synonyme.';
						break;
					}
				}					

			}
				
			fclose($handle);
		}
	}

	//on vérifie que le referrer est cohérent
	if ( (	$AR = get_option( $GLOBALS['activated_refere']["id"], NOT_EXISTS )
			AND $AR !==  'false' ) 
		&& (strpos($GLOBALS['jlaantispam_HTTP_REFERER'],esc_url( home_url( '/' ) )) === false) ) {
		$spam = true;
		$IDFiltreSpam = 2;
		$erreurFiltreSpam = 'Vous n\'êtes pas autorisé à poster un message.';
	}
	
	//on empêche de publier des liens - si contient http:// ou https://
	if ( (	$ANL = get_option( $GLOBALS['activated_nolink']["id"], NOT_EXISTS )
			AND $ANL !==  'false' ) 
		&& (isset($_REQUEST['message']) && ( strpos($_REQUEST['message'],'http://') OR strpos($_REQUEST['message'],'https://') ) !== false) ) {
		$spam = true;
		$IDFiltreSpam = 3;
		$erreurFiltreSpam = 'Vous n\'êtes pas autorisé à poster un message avec un lien.';
	} 
	
	//on empêche de publier des email
	if ( (	$ANE = get_option( $GLOBALS['activated_noemail']["id"], NOT_EXISTS )
			AND $ANE !==  'false' )
		&& isset($_REQUEST['message']) && strpos($_REQUEST['message'],'@') ) {
		$spam = true;
		$IDFiltreSpam = 4;
		$erreurFiltreSpam = 'Vous n\'êtes pas autorisé à poster un message contenant une adresse mail.';
	} 

	//on vérifie que les 2 champs remplis via javascript sont identiques
	if( (	$ATHP = get_option( $GLOBALS['activated_twinHoneypotTestJS']["id"], NOT_EXISTS )
			AND $ATHP !==  'false' )
		&& (isset($_REQUEST['price1']) && isset($_REQUEST['price2']) && $_REQUEST['price1'] != $_REQUEST['price2']) ){
		$spam = true;
		$IDFiltreSpam = 5;
		$erreurFiltreSpam = 'Vous n\'êtes pas autorisé à poster un message.';
	}

	//on vérifie que le pot de miel n'est pas rempli
	if( (	$AEHP = get_option( $GLOBALS['activated_emptyHoneypot']["id"], NOT_EXISTS )
			AND $AEHP !==  'false' )
		&& (isset($_REQUEST['mail_bis']) && $_REQUEST['mail_bis'] != '')){
		$spam = true;
		$IDFiltreSpam = 6;
		$erreurFiltreSpam = 'Vous n\'êtes pas autorisé à poster un message.';
	}

	//si mail envoyé trop rapidement après chargement de la page
	if( (	$ATBFS = get_option( $GLOBALS['activated_timeBeforeFirstSend']["id"], NOT_EXISTS )
			AND $ATBFS !==  'false' )
		&&
			(empty($_REQUEST['temps']) 
			OR ((jlaantispam_getTimestamp($token) - $_REQUEST['temps']) < $GLOBALS['activated_timeBeforeFirstSend']["data"]["time"])) 
	) {
		$spam = true;
		$IDFiltreSpam = 7;
		$erreurFiltreSpam = 'Veuillez attendre ' . $GLOBALS['activated_timeBeforeFirstSend']["data"]["time"] . ' secondes avant d\'envoyer un message.';
	}

	//si le mail précèdent a été envoyé il y a trop peu de temps
	if( (	$AEST = get_option( $GLOBALS['activated_emailSendingTimeout']["id"], NOT_EXISTS )
			AND $AEST !==  'false' )
		&& (!jlaantispam_isAllowedToSend($token, $GLOBALS['activated_emailSendingTimeout']["data"]["time"])) ) {
		jlaantispam_sauvegardeDuToken($token, $GLOBALS['jlaantispam_dossier_token']);
		$spam = true;
		$IDFiltreSpam = 8;
		$erreurFiltreSpam = 'Veuillez attendre ' . $GLOBALS['activated_emailSendingTimeout']["data"]["time"] . ' secondes avant de pouvoir renvoyer un message.';
	}
	jlaantispam_sauvegardeDuToken($token, $GLOBALS['jlaantispam_dossier_token']);

	// message d'erreur CF7 custom
	$erreurUtileFiltreSpam = "";
	if($erreurFiltreSpam){
		
		/* logs CF7 :
			$submission = WPCF7_Submission::get_instance();
			$submission->add_spam_log( array(
				'agent' => 'jla-antispam',
				reason' => erreurFiltreSpam
			));
		*/

		// status du msg => spam
		add_filter( 'wpcf7_ajax_json_echo', function( $response, $result ) use ( $erreurFiltreSpam ) {
			$response["message"] = sprintf( __($erreurFiltreSpam, "jlaantispam"), $GLOBALS['jlaantispam_HTTP_REFERER'] );
			return $response;
		}, 10,2);

		//$erreurUtileFiltreSpam : ça ne sert à rien de noter les erreurs génériques dans le fichier de logs, seulement les informations variables
		switch ($IDFiltreSpam) {
			case 1: // si cause du ban == mot interdis 
				$erreurUtileFiltreSpam = $bannedWord;
				break;
			
			default:break;
		}
	}

	jlaantispam_saveALog("CF7logs.csv", $IDFiltreSpam, $erreurUtileFiltreSpam);

	return $spam;
}


// Fonctions liées au token
function jlaantispam_creationDuToken($id) {
	$token = $id . "_" . time();
	return $token;
}
function jlaantispam_getTimestamp($token){
	return end(explode("_", $token));
}
function jlaantispam_getSessionID($token){
	$ts = "_" . jlaantispam_getTimestamp($token);
	return str_replace($ts,"",$token);
}
function jlaantispam_sauvegardeDuToken($token, $exportFolder){
	touch($exportFolder . $token);
}
function jlaantispam_isAllowedToSend($token, $max){
	$state = true;
	$token_timestamp = jlaantispam_getTimestamp($token);
	wp_mkdir_p( $GLOBALS['jlaantispam_dossier_token'] );
	$stored_user_token_files = new FilesystemIterator($GLOBALS['jlaantispam_dossier_token']);
	
	foreach ($stored_user_token_files as $stored_user_token_file) { // On parcours les token sauvegardé. Si un token match l'utilisateur et est récent, on refuse l'autorisation.
		$filename = $stored_user_token_file->getFilename();
		$stored_user_timestamp = jlaantispam_getTimestamp( $filename );
		$stored_user_sessID = jlaantispam_getSessionID( $filename );

		if ( (jlaantispam_getSessionID($token) == $stored_user_sessID) 
			&& ($stored_user_timestamp >= ($token_timestamp - $max)) 
		){
			$state = false;
		}
	}
	return $state;
}


// Fonctions liées aux mots bannis
function jlaantispam_getBannedWordsCats(){

	if (($handle = fopen(str_replace('\\', '/', __DIR__ . "/banned_words.csv"), "r")) === false) { return $handle; }
	if(($cats = fgetcsv($handle, 0, ";")) === false){ return $cats;}

	$filter_name = "jlaantispam_filter_deactivated_cats";
	$deactivated_cats = $GLOBALS['deactivated_cats']["value"]; 


	$saved_deactivated_cat = get_option( $filter_name, NOT_EXISTS ); // si l'option n'existe pas, get_options() retournera NOT_EXISTS

	if($saved_deactivated_cat !== NOT_EXISTS){
		$deactivated_cats = array_merge($saved_deactivated_cat, $deactivated_cats);
	}

	$catsData = [];
	foreach ($cats as $cat) {
		$catState = in_array($cat, $deactivated_cats);
		$catsData[] = array(
			"name" => 	$cat,
			"state" => 	$catState,
		);
	}

	return $catsData;
}

// Fonctions liées aux logs
function jlaantispam_getPluginUploadDataFolder(){
	if(!file_exists($GLOBALS['jlaantispam_uploads_data'])) {
		wp_mkdir_p($GLOBALS['jlaantispam_uploads_data']);
	}
	return $GLOBALS['jlaantispam_uploads_data'];
}
function jlaantispam_getLogsFolder(){
	mkdir($path = jlaantispam_getPluginUploadDataFolder() . $GLOBALS['jlaantispam_dossier_exportLogs'] . "/"); // false => problème de droits ?
	return $path;
}
function jlaantispam_saveALog($logsFile, $IDErreur, $msgUtileErreur){
	$logsFileUrl = jlaantispam_getLogsFolder() . $logsFile;

	$sane_post = sanitize_post($_POST);

	$dirtyPostKeys = array_keys($sane_post);
	$dirtyPostKeys_CF7Cleaned = array_filter( $dirtyPostKeys, function($postKey){ return strpos($postKey, "_wpcf7")=== false; });
	$postKeys_cleaned = array_diff( $dirtyPostKeys_CF7Cleaned, ["verif_rgpd", "price1", "price2", "mail_bis", "temps"] );
	
	foreach ($postKeys_cleaned as $postKey) {
		$dataFormulaire[] = $sane_post[$postKey];
	}

	$handle = fopen($logsFileUrl, "a");
	$line = [
		$IPUtilisateur  			= sanitize_text_field($_SERVER['REMOTE_ADDR']),
		$dateHeure  				= date('d/m/Y h:i:s a', time()),
		$IDBlocage  				= $IDErreur,
		$raisonBlocage  			= $msgUtileErreur,
		"",
		implode(",", $dataFormulaire),
	];
	// $line = array_merge($line, $dataFormulaire);
	fputcsv($handle, $line, ";");
	fclose($handle);
}

?>