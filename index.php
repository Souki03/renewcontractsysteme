<?php

// Système de logs
function addLogEvent($event) 
{
    $time = date("D, d M Y H:i:s");
    $time = "[".$time."] ";
 
    $event = $time.$event."\n";
 
	echo $event;
    file_put_contents("logs.txt", $event, FILE_APPEND);
}
// 
addLogEvent("Start"); 
echo '<br />';

try
{
	$db_config = array();
	$db_config['SGBD']	= 'mysql';   
	$db_config['HOST']	= 'localhost';
	$db_config['DB_NAME']	= 'stage';
	$db_config['USER']	= 'root';
	$db_config['PASSWORD']	= '';
	$db_config['OPTIONS']	= array(
		// Activation des exceptions PDO :
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		// Change le fetch mode par défaut sur FETCH_ASSOC ( fetch() retournera un tableau associatif ) :
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
	);
	
	$pdo = new PDO($db_config['SGBD'] .':host='. $db_config['HOST'] .';dbname='. $db_config['DB_NAME'],
	$db_config['USER'],
	$db_config['PASSWORD'],
	$db_config['OPTIONS']);
	unset($db_config); 
    
}

catch(Exception $e)
{
	die('Erreur de connexion à la bdd avec PDO : ' . $e->getMessage()); // Message d'erreur en cas d'échec
	addLogEvent('Erreur de connexion à la bdd avec PDO : ' . $e->getMessage()); 
}
addLogEvent("Connexion avec PDO réussi"); 
echo '<br />';
// Fin de la connexion PDO


class Contracts
{
    private $pdo;
    private $contracts;
    public function __construct(PDO $pdo = null)
    {
        if(!$pdo) throw new Exception("L'object PDO n'est pas défini.");
        
        $this->pdo = $pdo;
        $this->contracts = [];
    }
    
    /**
     * Read the value of contracts
     *
     * @return  self
     */ 
    
    public function readContracts()
    {
    $stmt = $this->pdo->query('SELECT contractID, free_period FROM companies_contracts WHERE ending_date >= DATE(NOW())
    AND ending_date <= DATE(DATE_ADD(DATE(NOW()), INTERVAL 5 DAY))
    AND automatic_renewal = 1
    AND automatic_renewal_type = "external"
    AND active = 1;');
    $this->contracts = $stmt->fetchAll();
        return $this;
    }
    
    /**
     * Read the value of contracts
     *
     * @return  self
     */ 
    
    public function renewContracts()
    { 
	
        foreach($this->contracts as $contract) {
            if($contract['free_period'] > 0) {
                $this->execRenewContract($contract['contractID'], $contract['free_period']);
            }
            else {
                // execute paiement
                $resultPaiement = true;

                if($resultPaiement) {
                    $this->execRenewContract($contract['contractID']);
                }
            }
        }

        return $this;
    }
    
    /**
     * Read the value of contracts
     *
     * @return  self
     */ 

    private function execRenewContract(Int $contractID = null, Int $free_period = null)
    {
        
            if(!$contractID){ return false;
                addLogEvent("Erreur"); 
            }
            if($free_period > 0){ 
                $stmt = $this->pdo->prepare('UPDATE companies_contracts SET free_period = free_period-1, ending_date = ADDDATE(ending_date, INTERVAL validity_period MONTH) WHERE contractID = :contractID');
            }
            else {
                $stmt = $this->pdo->prepare('UPDATE companies_contracts SET ending_date = ADDDATE(ending_date, INTERVAL validity_period MONTH) WHERE contractID = :contractID');
            }
            $stmt->bindParam(':contractID', $contractID);
            $stmt->execute();
            addLogEvent("Ajout d'un mois au contrat n° " . $contractID); 
            
        

        return $this;
    }

    
}


try {
    $Contracts = new Contracts($pdo);
    $Contracts->readContracts($pdo);
    $Contracts->renewContracts();
} catch(Exception $e) {
    echo $e->getMessage();
}


// 
addLogEvent("Fin"); 
?>
