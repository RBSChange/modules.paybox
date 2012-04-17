<?php
/**
 * paybox_PayboxconnectorService
 * @package modules.paybox
 */
class paybox_PayboxconnectorService extends payment_ConnectorService
{
	/**
	 * @var paybox_PayboxconnectorService
	 */
	private static $instance;

	/**
	 * @return paybox_PayboxconnectorService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return paybox_persistentdocument_payboxconnector
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_paybox/payboxconnector');
	}

	/**
	 * Create a query based on 'modules_paybox/payboxconnector' model.
	 * Return document that are instance of modules_paybox/payboxconnector,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_paybox/payboxconnector');
	}
	
	/**
	 * Create a query based on 'modules_paybox/payboxconnector' model.
	 * Only documents that are strictly instance of modules_paybox/payboxconnector
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_paybox/payboxconnector', false);
	}
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		if ($this->ping($document) === false)
		{
			$this->setActivePublicationStatusInfo($document, '&modules.paybox.bo.general.ping-error;');
			return false;
		}
		$result = parent::isPublishable($document);
		return $result;
	}
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $document
	 * @return string or false
	 */	
	protected function ping($document)
	{
		$binaryPath = f_util_FileUtils::buildWebeditPath('bin', $document->getBinaryName());
		if (file_exists($binaryPath))
		{
			$cmd = $binaryPath . ' PBX_MODE=4 PBX_PING=1 PBX_SITE='.$document->getPbxSite().' PBX_RANG='.$document->getPbxRang().' PBX_IDENTIFIANT='.$document->getPbxIdentifiant();
			$serverUrl = $document->getBankServerUrl();
			if (!empty($serverUrl))
			{
				$cmd .= ' PBX_PAYBOX="' .$serverUrl. '"';
			}
			try 
			{
				$s = f_util_System::exec($cmd);
				if (strpos($s, 'Serveur: ') === 0)
				{
					$serveur = trim(substr($s, 9));
					return $serveur;
				}
				else
				{
					Framework::error(__METHOD__ . ' Invalid response: ' . var_export($s, true));
				}
			} 
			catch (Exception $e) 
			{
				Framework::exception($e);
			}
		}
		return false;
	}
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @return array
	 */
	protected function getBankingURLArray($connector, $order)
	{
		$billQueryString = "?billId=" . $order->getPaymentId();
		$urls = array('PBX_EFFECTUE' => LinkHelper::getActionUrl('paybox', 'BankSuccess') . $billQueryString,
				'PBX_REFUSE' => LinkHelper::getActionUrl('paybox', 'BankReject') . $billQueryString,
				'PBX_ANNULE' => LinkHelper::getActionUrl('paybox', 'BankResponse') . $billQueryString,
				'PBX_REPONDRE_A' => LinkHelper::getActionUrl('paybox', 'BankListener') . $billQueryString);
		$urls['PBX_PAYBOX'] = $this->ping($connector);
		return $urls;
	}
	
	/**
	 * ISO 4217
	 * Currencies map : Change4 => paybox
	 */
	private $currencyMap = array(
			"EUR"	=> 978,		// Euro
			"GBP"	=> 826,		// Livre sterling
			"CHF"	=> 756,		// Franc suisse
			"USD"	=> 840		// US Dollar
	);
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @return string
	 */
	private function buildPBXCmd($connector, $order)
	{
		return $order->getPaymentReference();
	}
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @return integer
	 */	
	private function buildPBXDevise($connector, $order)
	{
		if (isset($this->currencyMap[$order->getPaymentCurrency()]))
		{
			return $this->currencyMap[$order->getPaymentCurrency()];
		}
		return $this->currencyMap["EUR"]; 
	}
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @return string
	 */
	private function buildPBXTotal($connector, $order)
	{
		$amount = $order->getPaymentAmount();
		return strval(round($amount * 100, 0));
	}
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @return string
	 */
	private function buildPBXRetour($connector, $order)
	{
		return 'amount:M;ref:R;auto:A;trans:T;idtrans:S;dt:W;hr:Q;pt:P;carte:C;country:I;validity:D;error:E;sign:K';
	}
		
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @return string 
	 */
	private function buildPBXPorteur($connector, $order)
	{
		$ba = $order->getPaymentBillingAddress();
		return $ba !== null && $ba->getEmail() ? $ba->getEmail() : 'noreply@paybox.com';
	}
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @return string null|CARTE|PAYPAL|UNEURO|NETRESERVE
	 */
	private function builPBXTypepaiement($connector, $order)
	{
		return null;
	}

	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @return string null|CB|VISA|EUROCARD_MASTERCARD|E_CARD|AMEX|DINERS|JCB|COFINOGA|SOFINCO|AURORE|CDGP|24H00|RIVEGAUCHE // PAYPAL // UNEURO // NETCDGP|NETCOF
	 */
	private function builPBXTypecarte($connector, $order)
	{
		return null;
	}
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 * @param array $bankingURLArray
	 * @return string
	 */	
	private function generatePBXData($connector, $order, &$formParam)
	{
		$params = array('PBX_MODE=4', 'PBX_OUTPUT=D');
		$params[] = 'PBX_SITE=' . $connector->getPbxSite();
		$params[] = 'PBX_RANG=' . $connector->getPbxRang();
		$params[] = 'PBX_IDENTIFIANT=' . $connector->getPbxIdentifiant();
		$params[] = 'PBX_CMD=' .  $this->buildPBXCmd($connector, $order);
		$params[] = 'PBX_TOTAL=' . $this->buildPBXTotal($connector, $order);
		$params[] = 'PBX_DEVISE=' .$this->buildPBXDevise($connector, $order);		
		$params[] = 'PBX_PORTEUR="' . $this->buildPBXPorteur($connector, $order). '"';
		$params[] = 'PBX_RETOUR="' . $this->buildPBXRetour($connector, $order). '"';
		$params[] = 'PBX_REPONDRE_A="'. $formParam['PBX_REPONDRE_A']  . '"';
		
		$binaryPath = f_util_FileUtils::buildWebeditPath('bin', $connector->getBinaryName());
		$cmd = $binaryPath . ' ' . implode(' ', $params);
		$s = f_util_System::exec($cmd);
		if (!empty($s) && strpos($s, 'Content-type:') === false)
		{
			$formParam['PBX_DATA'] = $s;
			unset($formParam['PBX_REPONDRE_A']);
		}
		else
		{
			throw new Exception("Invalid PBX_DATA : " . var_export($s, true));
		}
	}
	
	
	/**
	 * @param paybox_persistentdocument_payboxconnector $connector
	 * @param payment_Order $order
	 */
	public function setPaymentInfo($connector, $order)
	{
		$transactionId = $order->getPaymentTransactionId();
		if ($transactionId != null)
		{
			$this->setPaymentStatus($connector, $order);
			return;
		}	
		
		$params = $this->getBankingURLArray($connector, $order);
		$this->generatePBXData($connector, $order, $params);
	
		//Set session Info for callback
		$sessionInfo = array('orderId' => $order->getPaymentId(),
				'connectorId' => $connector->getId(),
				'lang' => RequestContext::getInstance()->getLang(),
				'paymentAmount' => $order->getPaymentAmount(),
				'currencyCodeType' => $order->getPaymentCurrency(),
				'paymentURL' => $order->getPaymentCallbackURL());
				
		$this->setSessionInfo($sessionInfo);
		$bankServerURL = $params['PBX_PAYBOX'];
		unset($params['PBX_PAYBOX']);
		
		$messageArray= array();
		$messageArray[] ='<form name="paybox" method="POST" action="'. $bankServerURL.'">';
		foreach ($params as $key => $value) 
		{
			$messageArray[] ='	<input type="hidden" name="'. $key.'" value="'. htmlspecialchars($value, ENT_COMPAT, 'utf-8').'" />';
		}
		
		$submitText = LocaleService::getInstance()->transFO('m.paybox.fo.payment-action', array('attr'));
		$messageArray[] = '	<input type="submit" class="button" value="' . $submitText . '" />';
		$messageArray[] ='</form>';
		$connector->setHTMLPayment(implode(PHP_EOL, $messageArray));
	}
	
	/**
	 * @param string $returnType [Listener|Cancel|Success|
	 * @param string $data
	 * @throws Exception
	 * @return payment_Transaction
	 */
	public function getBankResponse($returnType, $data)
	{
		$response = new payment_Transaction();
		$response->setRawBankResponse($data);
		
		if (!preg_match('/^billId=([0-9]+)&/', $data, $billInfo))
		{
			throw new Exception("Invalid bill identification");
		}
		
		$order = DocumentHelper::getDocumentInstance($billInfo[1]);
		if (!($order instanceof payment_Order))
		{
			throw new Exception("Invalid bill reference: " . $billInfo[1]);
		}
		$response->setLang($order->getPaymentLang());
		$response->setOrderId($order->getId());
		
		$connector = $order->getPaymentConnector();
		if (!($connector instanceof paybox_persistentdocument_payboxconnector))
		{
			throw new Exception("Invalid connector");
		}
		$response->setConnectorId($connector->getId());
		if ($returnType === 'Listener')
		{
			$data = substr($data, strlen($billInfo[0]));
		}
		
		$keyfile = f_util_FileUtils::buildWebeditPath('modules','paybox','lib','pubkey.pem');		
		$checkSig = $this->pbxVerSign($data, $keyfile, true);		
		if($checkSig != 1 )
		{
			if ($checkSig == 0)
			{
				throw new Exception("Signature invalide: donnees alterées ou signature falsifiée");
			}
			else
			{
				throw new Exception("Erreur lors de la vérification de la signature");
			}
		}
		
		$dataArray = array();
		parse_str($data, $dataArray);

		$amount = $this->buildPBXTotal($connector, $order);
		if ($amount !== $dataArray['amount'])
		{
			throw new Exception("Invalid Amount (".$dataArray['amount'].") expected: " . $amount);
		}
		$response->setAmount($order->getPaymentAmount());
		$response->setCurrency($order->getPaymentCurrency());
		
		if ($dataArray['error'] === '00000')
		{
			$response->setAccepted();
			$response->setTransactionId($dataArray['auto']);
			$response->setTransactionText(LocaleService::getInstance()->transFO('m.paybox.fo.payment-success', array('ucf'), array('code' => $dataArray['carte'])));
			$y = substr($dataArray['dt'], 4); $m = substr($dataArray['dt'], 2, 2); $d = substr($dataArray['dt'], 0, 2);
			$localDate = $y . '-' .  $m . '-' .  $d . ' ' . $dataArray['hr'];
			$date = date_Converter::convertDateToGMT($localDate);
			$response->setDate($date);
		}
		else
		{
			$response->setFailed();
			$response->setTransactionId('ERROR-'. $dataArray['error']);
			$response->setTransactionText(LocaleService::getInstance()->transFO('m.paybox.fo.payment-failed', array('ucf')));			
		}
	
		return $response;
	}

	/**
	 * Parse order paymentResponse
	 * @param payment_Order $order
	 * @return array associative array<String, String>
	 */
	public function parsePaymentResponse($order)
	{
		$dataArray = array();
		$data = $order->getPaymentResponse();
		if (empty($data))
		{
			return$dataArray;
		}
		parse_str($data, $dataArray);
		unset($dataArray['sign']);
		switch ($dataArray['error']) 
		{
			case '00001': $dataArray['errorTXT'] = 'La connexion au centre d\'autorisation a échoué.'; break;
			case '00003': $dataArray['errorTXT'] = 'Erreur Paybox.'; break;
			case '00004': $dataArray['errorTXT'] = 'Numéro de porteur ou cryptogramme visuel invalide.'; break;
			case '00008': $dataArray['errorTXT'] = 'Date de fin de validité incorrecte.'; break;
			case '00009': $dataArray['errorTXT'] = 'Erreur de création d\'un abonnement.'; break;
			case '00010': $dataArray['errorTXT'] = 'Devise inconnue.'; break;		
			case '00011': $dataArray['errorTXT'] = 'Montant incorrect.'; break;
			case '00015': $dataArray['errorTXT'] = 'Paiement déjà effectué.'; break;
			case '00016': $dataArray['errorTXT'] = 'Abonné déjà existant (inscription nouvel abonné).'; break;
			case '00021': $dataArray['errorTXT'] = 'Carte non autorisée.'; break;
			case '00029': $dataArray['errorTXT'] = 'Carte non conforme.'; break;
			case '00006': $dataArray['errorTXT'] = 'Accès refusé ou site/rang/identifiant incorrect.'; break;
			case '00030': $dataArray['errorTXT'] = 'Temps d\'attente > 15 mn par l\'internaute/acheteur au niveau de la page de paiements.'; break;
			case '00033': $dataArray['errorTXT'] = 'Code pays de l\'adresse IP du navigateur de l\'acheteur non autorisé.'; break;
			
			case '00102': $dataArray['errorTXT'] = 'contacter l\'émetteur de carte.'; break;
			case '00103': $dataArray['errorTXT'] = 'commerçant invalide.'; break;
			case '00104': $dataArray['errorTXT'] = 'conserver la carte.'; break;
			case '00105': $dataArray['errorTXT'] = 'ne pas honorer.'; break;
			case '00107': $dataArray['errorTXT'] = 'conserver la carte, conditions spéciales.'; break;
			case '00108': $dataArray['errorTXT'] = 'approuver après identification du porteur.'; break;
			case '00112': $dataArray['errorTXT'] = 'transaction invalide.'; break;
			case '00113': $dataArray['errorTXT'] = 'montant invalide.'; break;
			case '00114': $dataArray['errorTXT'] = 'numéro de porteur invalide.'; break;
			case '00115': $dataArray['errorTXT'] = 'émetteur de carte inconnu.'; break;
			case '00117': $dataArray['errorTXT'] = 'annulation client.'; break;
			case '00119': $dataArray['errorTXT'] = 'répéter la transaction ultérieurement.'; break;
			case '00120': $dataArray['errorTXT'] = 'réponse erronée (erreur dans le domaine serveur).'; break;
			case '00124': $dataArray['errorTXT'] = 'mise à jour de fichier non supportée.'; break;
			case '00125': $dataArray['errorTXT'] = 'impossible de localiser l\'enregistrement dans le fichier.'; break;
			case '00126': $dataArray['errorTXT'] = 'enregistrement dupliqué, ancien enregistrement remplacé.'; break;
			case '00127': $dataArray['errorTXT'] = 'erreur en « edit » sur champ de mise à jour fichier.'; break;
			case '00128': $dataArray['errorTXT'] = 'accès interdit au fichier.'; break;
			case '00129': $dataArray['errorTXT'] = 'mise à jour de fichier impossible.'; break;
			case '00130': $dataArray['errorTXT'] = 'erreur de format.'; break;
			case '00138': $dataArray['errorTXT'] = 'nombre d\'essais code confidentiel dépassé.'; break;
			case '00141': $dataArray['errorTXT'] = 'carte perdue.'; break;
			case '00143': $dataArray['errorTXT'] = 'carte volée.'; break;
			case '00151': $dataArray['errorTXT'] = 'provision insuffisante ou crédit dépassé.'; break;
			case '00154': $dataArray['errorTXT'] = 'date de validité de la carte dépassée.'; break;
			case '00155': $dataArray['errorTXT'] = 'code confidentiel erroné.'; break;
			case '00156': $dataArray['errorTXT'] = 'carte absente du fichier.'; break;
			case '00157': $dataArray['errorTXT'] = 'transaction non permise à ce porteur.'; break;
			case '00158': $dataArray['errorTXT'] = 'transaction interdite au terminal.'; break;
			case '00159': $dataArray['errorTXT'] = 'suspicion de fraude.'; break;
			case '00160': $dataArray['errorTXT'] = 'l\'accepteur de carte doit contacter l\'acquéreur.'; break;
			case '00161': $dataArray['errorTXT'] = 'dépasse la limite du montant de retrait.'; break;
			case '00163': $dataArray['errorTXT'] = 'règles de sécurité non respectées.'; break;
			case '00168': $dataArray['errorTXT'] = 'réponse non parvenue ou reçue trop tard.'; break;
			case '00175': $dataArray['errorTXT'] = 'nombre d\'essais code confidentiel dépassé.'; break;
			case '00176': $dataArray['errorTXT'] = 'porteur déjà en opposition, ancien enregistrement conservé.'; break;
			case '00190': $dataArray['errorTXT'] = 'arrêt momentané du système.'; break;
			case '00191': $dataArray['errorTXT'] = 'émetteur de cartes inaccessible.'; break;
			case '00194': $dataArray['errorTXT'] = 'demande dupliquée.'; break;
			case '00196': $dataArray['errorTXT'] = 'mauvais fonctionnement du système.'; break;
			case '00197': $dataArray['errorTXT'] = 'échéance de la temporisation de surveillance globale.'; break;
		}
		return $dataArray;
	}
	
	/**
	 * Chargement de la clé (publique par défaut)
	 * @param string $keyfile
	 * @param boolean $pub
	 * @param string $pass
	 */
	private function loadKey( $keyfile, $pub=true, $pass='' ) 
	{
		$fp = $filedata = $key = false; 
		$fsize = filesize( $keyfile ); 
		if (!$fsize) {return false;} 
		
		$fp = fopen($keyfile, 'r'); 
		if(!$fp) {return false;}
		$filedata = fread($fp, $fsize);
		fclose($fp);  

		if (!$filedata) return false;  
		if ($pub)
		{
			$key = openssl_pkey_get_public($filedata);
		}
		else
		{
			$key = openssl_pkey_get_private(array($filedata, $pass));
		}
		
		if (!$key)
		{
			Framework::error(openssl_error_string());
		}
		return $key;
	}	
	
	/**
	 * Renvoi les donnes signees et la signature
	 * @param string $qrystr
	 * @param boolean $url
	 * @return array<$data, $sig>
	 */
	private function getSignedData($qrystr, $url) 
	{
		$pos = strrpos($qrystr, '&');
		$data = substr($qrystr, 0, $pos);
		$pos= strpos($qrystr, '=', $pos) + 1;
		$sig = substr($qrystr, $pos);
		if($url) $sig = urldecode($sig);
		$sig = base64_decode($sig);
		return array($data, $sig);
	}	
	
	/**
	 * Verification signature Paybox
	 * @param string $qrystr
	 * @param string $keyfile
	 * @param boolean $url
	 * @return integer 1 si valide, 0 si invalide, -1 si erreur
	 */
	private function pbxVerSign($qrystr, $keyfile, $url) 
	{ 
		$key = $this->loadKey($keyfile);
		if(!$key) return -1; 
		
		list($data, $sig) = $this->getSignedData($qrystr, $url);
		return openssl_verify($data, $sig, $key); // verification : 1 si valide, 0 si invalide, -1 si erreur
	}		
}