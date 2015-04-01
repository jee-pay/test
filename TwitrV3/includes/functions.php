<?php 

function newPDO() {
    try {
        $oPDO = new PDO('mysql:host=localhost;dbname='.BDD, USER_BDD, MDP_BDD);
    } catch (PDOException $ex) {
        echo '<br/>';
        echo "Echec lors de la connexion à MySQL : (" . $ex->getCode() . ") "; 
        echo $ex->getMessage();
        exit();
    }
    
    return $oPDO;
}

/*
 * Création d'un compte utilisateur
 * @parameter = array
 */

    function decryptPassword($username, $password, $hash) {
        $passwordtemp = $username[0] . $password . mb_substr($username, -1, 1);
        if(version_compare(PHP_VERSION, '5.5') >= 0) {
            return password_verify($passwordtemp, $hash);
            //return hash_equals($hash, crypt($passwordtemp. PRIVATE_KEY)); // v. >= 5.6
        } else {
            return $hash == crypt($passwordtemp,PRIVATE_KEY) ? TRUE : FALSE;
        }
        
    }

     function cryptPassword($username, $password) {

        $passwordtemp = $username[0] . $password . mb_substr($username, -1, 1);

        if(version_compare(PHP_VERSION, '5.5') >= 0) {
            $cryptedPassword = password_hash($passwordtemp, PASSWORD_BCRYPT);
        } else {
            $cryptedPassword = crypt($passwordtemp, PRIVATE_KEY);
        }
        
        return $cryptedPassword;
    }

    function checkCompte($tabUser) {

        $oPDO = newPDO();

        $reqGetUser = "SELECT idUser,login,password,avatar FROM users WHERE ";
        $reqGetUser .= "login = :login LIMIT 1";

        $pdoGetUser = $oPDO->prepare($reqGetUser);
        $pdoGetUser->execute(array('login' => $tabUser['login']));

        $resGetUser = $pdoGetUser->fetch(PDO::FETCH_ASSOC);
        
        if($pdoGetUser->errorCode() != 0) {
            $oPDO = NULL;
            return FALSE;
        }
        
        $cryptedPassword = decryptPassword($tabUser['login'], $tabUser['password'], $resGetUser['password']);
        /*var_dump($tabUser);
        var_dump($resGetUser);
        var_dump($cryptedPassword); die();  */ 
        
        
        if($cryptedPassword == TRUE ) {
            return $resGetUser;
        } else {
            return FALSE;
        }
        
    }
    
    
    function creerCompte($tabCompte) {

    //var_dump($tabCompte);
        $resCompte = array();
    
        $oPdo = newPDO();
    
        $cryptedPassword = cryptPassword($tabCompte['login'], $tabCompte['password']);

        $reqCreateUser = 'INSERT INTO users (login,mail,password,created_on) ';
        $reqCreateUser .= 'VALUES (:login,:mail,:password,NOW())';
        
        $data = $oPdo->prepare($reqCreateUser);
        $data->execute(array('login' => $tabCompte['login'],
            'mail' => $tabCompte['mail'],
            'password' => $cryptedPassword)
        );

        if($data->errorCode() == 0 ) {
                $last_id = $oPdo->lastInsertId();

                $resCompte['msg'] = TRUE;
                $resCompte['id'] = $last_id;
        } else {
            $error = $data->errorInfo();
            $resCompte = $error['2'];
        }
        
        return $resCompte;
    }

    
    function createImage($fileImage,$iduser) {
    
        //récupération de l'extension du fichier
        /*
         * strrchr — Trouve la dernière occurrence d'un caractère dans une chaîne, ici le . 
         * la fonction va renvoyer .extension ex : .png
         * substr — Retourne un segment de chaîne, dans .extenstion on va partir du 
         * premier caractère (1) jusqu'à la fin de la chaine (pas de paramère)
         * strtolower - Renvoie une chaîne en minuscules
         */
        
        //Extensions autorisées
        $tabExtensions = array('jpg','jpeg','png', 'gif');
    
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realType = finfo_file($finfo, $fileImage['tmp_name']);
        
        $extensionFichier = mb_strtolower(mb_substr(mb_strrchr($realType,'/'),1));
        if(!in_array($extensionFichier, $tabExtensions) ) {

            return 'Extension non valide';
        }
    
        $real_path = (md5($fileImage['name'].time())).'.'.$extensionFichier;
        
        $uploadResult = move_uploaded_file($fileImage['tmp_name'], 
               IMG_DIR.DIRECTORY_SEPARATOR.$real_path
                );
        
        if(!$uploadResult) {
            return 'Problème lors de l\'upload';
        }
        
	$oPDO = newPDO();
        // Création de la requête
	$reqImage = 'UPDATE users SET avatar = :imagename WHERE idUser = :iduser';
        $data = $oPDO->prepare($reqImage);
        $data->bindValue('imagename', $real_path);
        $data->bindValue('iduser', $iduser);

        $data->execute();
        
        
//        if(mysqli_errno($linkBDD) != 0) {
//            $oPDO = NULL;
//            return 'Problème de mise à jour de l\'avatar ...';
//        }
        
        //echo $resImages;
	//echo $resImages;
}

function nbTwits($twitligne = 15,$table='messages'){
    $oPDO = newPDO();
    try {
        $reqNbTwits = 'SELECT count(idMessage) as nbtwit FROM '.$table;
        $data = $oPDO->prepare($reqNbTwits);
        $data->execute();
        $nbTwits = $data->fetch(pdo::FETCH_ASSOC);
        //$nbpage1 = $nbTwits['nbtwit'] % $twitligne ;
        $nbpages = ceil ($nbTwits['nbtwit'] / $twitligne) ; //arrondit au nb superieur
        //
        
        return array(
            'nbtwits' => $nbTwits['nbtwit'],
            'nbpages'=> $nbpages);

    } catch (PDOException $ex) {
        echo '<br/>';
        echo "Echec lors de la récupération des données : (" . $ex->getCode() . ") "; 
        echo $ex->getMessage();
        exit();
    }
    
}

function getUserID($login) {
    $oPDO = newPDO();
    $reqUserID = 'SELECT idUser FROM users WHERE login = :login LIMIT 1';
    
    $data = $oPDO->prepare($reqUserID);
    $data->bindValue('login', $login);
    $data->execute();
    $resUserID = $data->fetch();
    return $resUserID['idUser'];
    
} 

function getFavoris($idUser,$detail=FALSE,$pas=0) {
    $oPDO = newPDO();
    if($detail == FALSE) {
        $reqFavoris = 'SELECT idMessage FROM favoris WHERE idUser = :iduser';
        $data = $oPDO->prepare($reqFavoris);
        $data->bindValue('iduser', (int)$idUser, PDO::PARAM_INT );
        $data->execute();
        $resFavoris = $data->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $reqFavoris = 'SELECT users.*, relMessageUsers.*, messages.*,CONCAT("@",torigin.login) as uretwit ';
        $reqFavoris .= 'FROM `favoris` JOIN relMessageUsers ON ';
        $reqFavoris .= 'relMessageUsers.idMessage=favoris.idMessage JOIN messages ON ';
        $reqFavoris .= 'messages.idMessage=favoris.idMessage ';
        $reqFavoris .= 'JOIN users on users.idUser = favoris.idUSer ';
        $reqFavoris .= 'LEFT OUTER JOIN users torigin ON torigin.idUser = relMessageUsers.idUser ';
        $reqFavoris .= 'WHERE favoris.idUSER=:iduser AND retwit=0 ';
        $reqFavoris .= 'ORDER BY relMessageUsers.created_on DESC LIMIT :pas, 15 ';
        
        $data = $oPDO->prepare($reqFavoris);
        $data->bindValue('iduser', (int)$idUser, PDO::PARAM_INT );
        $data->bindValue('pas', (int)$pas, PDO::PARAM_INT );
        $data->execute();
        $resFavoris = $data->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $oPDO= NULL;
    return $resFavoris;
    
}

function setFavoris($idUser,$idTwit) {
    
}

function getRetwit($idUser) {
    $oPDO = newPDO();
    $reqFavoris = 'SELECT idMessage FROM favoris WHERE users = :iduser';
    $data = $oPDO->prepare($reqFavoris);
    $data->bindValue('iduser', (int)$idUser, PDO::PARAM_INT );
    $data->execute();
    
}

function setRetwit($idUser,$idTwit) {
    
}

function getTwits ($pas=0){
    
    //echo $pas;
    
    $oPDO = newPDO();

    try {
        $reqTwits = 'SELECT users.*, relMessageUsers.*, messages.*,CONCAT("@",torigin.login) as uretwit ';
        $reqTwits .= 'FROM messages JOIN relMessageUsers ON relMessageUsers.idMessage = messages.idMessage ';
        $reqTwits .= 'JOIN users ON users.idUser = relMessageUsers.idUser ';
        $reqTwits .= 'LEFT OUTER JOIN users torigin ON torigin.idUser = relMessageUsers.idUserOrigin ';
        $reqTwits .= 'ORDER BY relMessageUsers.created_on DESC LIMIT :pas, 15 ';

//echo $reqTwits;
        
        $data = $oPDO->prepare($reqTwits);
        $data->bindValue('pas', (int)$pas, PDO::PARAM_INT );
        $data->execute();

        $resTwits = $data->fetchAll(pdo::FETCH_ASSOC);

//        var_dump($resTwits);
//        die();
        return $resTwits;

    } catch (PDOException $ex) {
        echo '<br/>';
        echo "Echec lors de la récupération des données : (" . $ex->getCode() . ") "; 
        echo $ex->getMessage();
        exit();
    }
    
}


function newTwit($twit,$idUser) {
    try {
        
        $oPDO = newPDO();
        
        $ReqTwit = 'INSERT INTO messages (message) VALUES (:message)';
        $oTwit = $oPDO->prepare($ReqTwit);
        $oTwit->bindValue('message', $twit);
        $oTwit->execute();
        
        $idTwit = $oPDO->lastInsertId();
        
        $reqRelTwit = 'INSERT INTO relMessageUsers (idMessage, idUser, created_on) ';
        $reqRelTwit .= 'VALUES (:idmessage, :iduser, NOW())';


        $data = $oPDO->prepare($reqRelTwit);
        $data->bindValue('idmessage',$idTwit, PDO::PARAM_INT );
        //$data->bindValue('iduser', (int)$reqRetwit['origin'], PDO::PARAM_INT );
        $data->bindValue('iduser', (int)$idUser, PDO::PARAM_INT );
        $data->execute();
        
        
    } catch (PDOException $ex) {
        echo $ex->getMessage();
        die();
    }
}

function newAction($tabTwit,$idUSer) {
    
    try {
        
        if(!is_array($tabTwit)) {
            throw new Exception('doit être un tableau', '9999');
        }
        
        
        //var_dump($tabTwit);
        
        
        $oPDO = newPDO();
        
        if(!is_null($tabTwit['rt']) && !is_null($tabTwit['origin'])) {
                $reqRetwit = 'INSERT INTO relMessageUsers (idMessage, idUser, created_on, retwit, idUserOrigin) ';
                $reqRetwit .= 'VALUES (:idmessage, :iduser, NOW(),1, :origin)';
                
                $data = $oPDO->prepare($reqRetwit);
                $data->bindValue('idmessage', (int)$tabTwit['rt'], PDO::PARAM_INT );
                $data->bindValue('origin', (int)$tabTwit['origin'], PDO::PARAM_INT );
                $data->bindValue('iduser', (int)$idUSer, PDO::PARAM_INT );
                $data->execute();
                //die();
                //echo $data->errorCode();                      
                
        } elseif(!is_null($tabTwit['fv'])) {        
                
                $reqFavoris = 'INSERT INTO favoris (idMessage, idUser) ';
                $reqFavoris .= 'VALUES (:idmessage, :iduser)';
                
                $data = $oPDO->prepare($reqFavoris);
                $data->bindValue('idmessage', (int)$tabTwit['fv'], PDO::PARAM_INT );
                $data->bindValue('iduser', (int)$idUSer, PDO::PARAM_INT );
                $data->execute();
                
        } elseif(!is_null($tabTwit['nfv'])) {        
                
                $reqFavoris = 'DELETE FROM favoris WHERE idMessage = :idmessage AND  idUser = :iduser ';
                
                $data = $oPDO->prepare($reqFavoris);
                $data->bindValue('idmessage', (int)$tabTwit['nfv'], PDO::PARAM_INT );
                $data->bindValue('iduser', (int)$idUSer, PDO::PARAM_INT );
                $data->execute();
        }
         elseif(!is_null($tabTwit['nrt'])) {        
                
                $reqFavoris = 'DELETE FROM relMessageUsers WHERE idMessage = :idmessage AND  idUser = :iduser ';
                
                $data = $oPDO->prepare($reqFavoris);
                $data->bindValue('idmessage', (int)$tabTwit['nrt'], PDO::PARAM_INT );
                $data->bindValue('iduser', (int)$idUSer, PDO::PARAM_INT );
                $data->execute();
        }
        
        
        
        $error = (int)$data->errorCode();
                if($error != 0) {
                    
                }
        
        $oPDO = NULL;
        
        
    } catch (PDOException $ex) {
        echo $ex->getMessage();
        die();
    }
    
}