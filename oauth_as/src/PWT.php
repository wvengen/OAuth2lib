<?php
class PWT {
// Operational attributes
	public $expiry = 0;
	public $issuedAt = 0;
	public $type = "";
	const signon = 'SIGNON';
	const signoff = 'SIGNOFF';
	const query = 'QUERY';
	const status = 'STATUS';
	public $issuer = "";
	public $audience = "";
	public $returnURL = "";
	public $attSource = "";
	public $result = "";
	const accept = 'ACCEPT';
	const reject = 'REJECT';
	public $reference = 0;
	public $inReferenceTo = "";
	public $hli = "";
	public $opoa = "";
	public $consent;
// User attributes
	public $attributes = array();

	protected $pubKeyDir = ".";
	protected $privateKey = "";
	protected $jwt = "";
	protected $jsonClaims = "";
	protected $header = array (
// Fixed values for the header: Type, signature algorithm and PAPI version
		"typ" => "JWT",
		"alg" => "RS256",
		"pav" => "2.0"
	);
	protected $claims = array ();

	protected static $iniIssuer = "";
	protected static $iniPrivateKey = "";
	protected static $iniPubKeyDir = ".";
	protected static $iniTTL = 3600;

	public static function init ($id="",$key="",$keydir=".",$ttl=3600) {
// Initialize static default values
		self::$iniIssuer = $id;
		self::$iniPrivateKey = $key;
		self::$iniPubKeyDir = $keydir;
		self::$iniTTL = $ttl;
	}
	public function __construct ($type=query,$ttl=0,$id="",$key="",$keydir="") {
		$this->type = $type;
		$this->issuedAt = time();
		$this->expiry = $this->issuedAt + (empty($ttl) ? self::$iniTTL : $ttl);
		$this->issuer = empty($id) ? self::$iniIssuer : $id;
		$this->privateKey = empty($key) ? self::$iniPrivateKey : $key;
		$this->pubKeyDir = empty($keydir) ? self::$iniPubKeyDir : $keydir;
		$this->reference = mt_rand();
	}

	public static function base64urldecode ($in) {
// Decodes a string encoded according to the JWT base64 format
		$iv1 = str_replace("_","/",$in);
		$iv2 = str_replace("-","+",$iv1);
		switch (strlen($iv2) % 4) {
			case 0:  break;
			case 2:  $iv2 .= "=="; break;
			case 3:  $iv2 .= "="; break;
			default: exit("FIXME: Illegal base64ulr string");
		}
		return base64_decode($iv2);
	}

	public static function base64urlencode ($in) {
// Encodes a string according to the JWT base64 format
		$iv1 = explode("=",base64_encode($in));
		$iv2 = str_replace("+","-",$iv1[0]);
		return str_replace("/","_",$iv2);
	}

	public function publicKeys ($dir) {
// Set the public key directory
		$this->pubKeyDir = $dir;
	}
	public function privateKey ($key) {
// Set the private key to be used for signing
		$this->privateKey = $key;
	}

	public function decode ($pwt) {
// Update this PWT with the contents of a wire-coded value
		$this->jwt = gzuncompress(PWT::base64urldecode($pwt));
		$this->readJSON();
	}
	public function encode () {
// Return a wire-coded value from this PWT
		$this->buildJWT();
		return PWT::base64urlencode(gzcompress($this->jwt));
	}
	public function reply ($result=accept,$ttl=0,$id="",$key="",$keydir="") {
// Create a new PWT to reply to this
		$thereply = new PWT($this->type,$ttl,$id,$key,$keydir);
		$thereply->result = $result;
		$thereply->inReferenceTo = $this->reference;
		return $thereply;
	}
	public function pst () {
// Create a new PST based on the data of this PWT
		$pst = new PST();
		$pst->issuedAt = $this->issuedAt;
		$pst->audience = $this->audience;
		$pst->attSource = $this->attSource;
		$pst->consent = $this->consent;
		$pst->attributes = $this->attributes;
		return $pst;
	}

	public function PWTinfo () {
// Echoes the current values for this PWT
		echo "Private Key: $this->privateKey\n";
		echo "Public Key Dir: $this->pubKeyDir\n";
		echo "JWT Header\n";
		foreach ($this->header as $k => $v) {echo "\t$k = $v\n";}
		echo "Claims\n";
		foreach ($this->claims as $k => $v) {
			echo "...$k: ";
			var_export($v);
			echo "\n";
		}
		echo "JSON Claims: $this->jsonClaims\n";
		echo "JWT: $this->jwt\n";
	}

        public function getJWT(){
            return $this->jwt;
        }

	public function dumpJWT () {
// Return the JWT value associated to this PWT
		if (empty($this->jwt)) { $this->buildJWT(); }
		return $this->jwt;
	}
	public function dumpJSON () {
// Return the JSON value for the claims part of the PWT
		if (empty($this->jsonClaims)) { $this->buildJSON(); }
		return $this->jsonClaims;
	}

	protected function readJSON () {
// Import JSON into the class properties
		$this->readJWT();
// Mandatory claims
		$this->expiry = $this->claims['exp'];
		$this->issuedAt = $this->claims['iat'];
		$this->type = $this->claims['typ'];
		$this->issuer = $this->claims['iss'];
		$this->reference = $this->claims['ref'];
		$this->audience = $this->claims['aud'];
		$this->returnURL = $this->claims['ret'];
// Claims in replies
		if (isset($this->claims['res'])) {
			$this->result = $this->claims['res'];
			$this->attSource = $this->claims['oas'];
			$this->inReferenceTo = $this->claims['irt'];
		}
// Claims in requests
		else {
			if (isset($this->claims['oas'])) {
				$this->attSource = $this->claims['oas'];
			}
		}
// Optional claims
		if (isset($this->claims['hli'])) {
			$this->hli = $this->claims['hli'];
		}
		if (isset($this->claims['opoa'])) {
			$this->opoa = $this->claims['opoa'];
		}
		if (isset($this->claims['con'])) {
			$this->consent = $this->claims['con'];
		}
// User attributes
		if (isset($this->claims['attr'])) {
			$this->attributes = $this->claims['attr'];
		}
	}

	protected function readJWT () {
// Decode JWT-style base64 into a JSON construct
		$jwtcomp = explode(".",$this->jwt);
		$jwth = json_decode(PWT::base64urldecode($jwtcomp[0]),true);
// Check header
		if ($jwth['typ'] != 'JWT' ||
		 $jwth['alg'] != 'RS256' ||
		 $jwth['pav'] != '2.0' ) {
			exit("FIXME: Invalid JWT header");
		}
// Check mandatory claims
		$this->jsonClaims = PWT::base64urldecode($jwtcomp[1]);
		$this->claims = json_decode($this->jsonClaims,true);
		if (!isset($this->claims['exp']) ||
		 !isset($this->claims['iat']) ||
		 !isset($this->claims['typ'] )||
		 !isset($this->claims['iss']) ||
		 !isset($this->claims['ref']) ||
		 !isset($this->claims['aud']) ||
		 !isset($this->claims['ret']) ||
		 (isset($this->claims['res']) && !isset($this->claims['oas'])) ||
		 (isset($this->claims['res']) && !isset($this->claims['irt']))) {
			exit("FIXME: Invalid JWT claims");
		}
// Validate signature
		$pubKey = 'file://'.$this->pubKeyDir.'/'.$this->claims['iss'].'.pem';
		$res = openssl_get_publickey($this->pubKeyDir);
		$jwts = PWT::base64urldecode($jwtcomp[2]);
		if (!openssl_public_decrypt($jwts, $safe, $res)) {
			exit ("FIXME: Cannot verify JWT signature. Check public key");
		}
		$signin = $jwtcomp[0].".".$jwtcomp[1];
		$rawhash = hash("sha256",$signin,true);
		if ($rawhash != $safe) {
			exit ("FIXME: JWT signature invalid");
		}
	}

	protected function buildJWT () {
// Update the JWT value for this PWT
		if (empty($this->privateKey)) {
			exit("FIXME: No private key available for signing the JWT");
		}
// Header
		$jwth = PWT::base64urlencode(json_encode($this->header));
// Claims
		$this->buildJSON();
		$jwtc = PWT::base64urlencode($this->jsonClaims);
// Signature
		$signin = $jwth.".".$jwtc;
		$res = openssl_get_privatekey($this->privateKey);
// Sample usign openssl_sign (and SHA1!!)
//		if (!openssl_sign($signin,$safe,$res,OPENSSL_ALGO_SHA1)) {
//			exit("FIXME: Cannot sign JWT. Check private key");
//		}
		$rawhash = hash("sha256",$signin,true);
		if (!openssl_private_encrypt($rawhash,$safe,$res)) {
			exit("FIXME: Cannot sign JWT. Check private key");
		}
		$jwts = PWT::base64urlencode($safe);
// Assemble JWT
		$this->jwt = $jwth.".".$jwtc.".".$jwts;
	}

	protected function buildJSON () {
// Update the JSON value for the claims of this PWT
// Mandatory claims
		$this->claims["exp"] = $this->expiry;
		$this->claims["iat"] = $this->issuedAt;
		$this->claims["typ"] = $this->type;
		$this->claims["iss"] = $this->issuer;
		$this->claims["ref"] = $this->reference;
		$this->claims["aud"] = $this->audience;
		$this->claims["ret"] = $this->returnURL;
// Claims in replies
		if (!empty($this->result)) {
			    $this->claims["oas"] = $this->attSource;
			    $this->claims["res"] = $this->result;
			    $this->claims["irt"] = $this->inReferenceTo;
		}
// Claims in requests
		else {
					if (!empty($this->attSource)) { $this->claims["oas"] = $this->attSource; }
		}
// Optional claims
		if (!empty($this->hli)) { $this->claims["hli"] = $this->hli; }
		if (!empty($this->opoa)) { $this->claims["opo"] = $this->opoa; }
		if (isset($this->consent)) { $this->claims["con"] = $this->consent; }
// User attributes
		$this->claims["attr"] = $this->attributes;
		$this->jsonClaims = json_encode($this->claims);
	}
}

class PST {
// Operational attributes
    public $expiry = 0;
    public $issuedAt = 0;
    public $audience = "";
    public $attSource = "";
    public $consent;
	public $randomBlock = "";
// User attributes
    public $attributes = array();

    protected $key = "";
    protected $jsonClaims = "";
    protected $claims = array ();

    protected static $iniKey = "";
	protected static $iniTTL = 3600;

    public static function init ($key="",$ttl=3600) {
// Initialize static default values
        self::$iniKey = $key;
		self::$iniTTL = $ttl;
    }

	public function __construct ($ttl=0,$key="",$iat=0) {
		$this->issuedAt = empty($iat) ? time() : $iat;
		$this->expiry = $this->issuedAt + (empty($ttl) ? self::$iniTTL : $ttl);
		$this->key = empty($key) ? self::$iniKey : $key;
		$this->randomBlock = mt_rand();
	}

	public function key ($key) {
// Set the key to be used for encrypting this PST
		$this->key = $key;
	}

	public function decode ($pst) {
// Update this PST with the contents of a wire-coded value
		$aesres = $this->initAES();
		if ($aesres) {
			$this->jsonClaims = gzuncompress(mdecrypt_generic($aesres,PWT::base64urldecode($pst)));
			$this->closeAES($aesres);
		}
		else {
			exit("FIXME: Cannot initialize AES module for decryption");
		}
		$this->readJSON();
	}
	public function encode () {
// Return a wire-coded value from this PST
		$this->buildJSON();
		$aesres = $this->initAES();
		if ($aesres) {
			return PWT::base64urlencode (mcrypt_generic($aesres,gzcompress($this->jsonClaims)));
			$this->closeAES($aesres);
		}
		else {
			exit("FIXME: Cannot initialize AES module for encryption");
		}
	}

	public function PSTinfo () {
// Echoes the current values for this PST
		if (empty($this->jsonClaims)) { $this->buildJSON(); }
 		echo "Claims\n";
        foreach ($this->claims as $k => $v) {
            echo "...$k: ";
            var_export($v);
            echo "\n";
        }
        echo "JSON Claims: $this->jsonClaims\n";
	}
	public function dumpJSON () {
// Return the JSON value for the claims of the PST
		if (empty($this->jsonClaims)) { $this->buildJSON(); }
		return $this->jsonClaims;
	}

	protected function readJSON() {
// Fill the class values from the JSON claims in the token
		$this->claims = json_decode($this->jsonClaims,true);
// Mandatory claims
		if (!isset($this->claims['exp']) ||
		 !isset($this->claims['iat']) ||
		 !isset($this->claims['aud']) ||
		 !isset($this->claims['oas'])) {
			exit("FIXME: Invalid PST claims");
		}
		$this->expiry = $this->claims['exp'];
		$this->issuedAt = $this->claims['iat'];
		$this->audience = $this->claims['aud'];
		$this->attSource = $this->claims["oas"];
// Optional claims
		if (isset($this->claims['con'])) {
            $this->consent = $this->claims['con'];
        }
// User attributes
        if (isset($this->claims['attr'])) {
            $this->attributes = $this->claims['attr'];
        }
// Random block
		$this->randomBlock = $this->claims['rnd'];
	}
	protected function buildJSON() {
// Update the contents of the JSON claims
// Mandatory claims
		$this->claims["exp"] = $this->expiry;
		$this->claims["iat"] = $this->issuedAt;
		$this->claims["aud"] = $this->audience;
		$this->claims["oas"] = $this->attSource;
// Optional claims
		if (isset($this->consent)) { $this->claims["con"] = $this->consent; }
// User attributes
		$this->claims["attr"] = $this->attributes;
// Random block
		$this->claims['rnd'] = $this->randomBlock;
		$this->jsonClaims = json_encode($this->claims);
	}
	protected function initAES() {
// Allocate and initialize the AES module
		$res = false;
		$mod = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($mod), MCRYPT_RAND);
		$key = substr($this->key, 0, mcrypt_enc_get_key_size($mod));
		if (mcrypt_generic_init($module, $key, $iv) === 0) {
			$res = $mod;
        }
        return $res;
	}
	protected function closeAES($mod) {
// Free resources associated to the AES module
		mcrypt_generic_deinit($mod);
		mcrypt_module_close($mod);
	}
}
?>
