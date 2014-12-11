<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:43:19 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Objects
 **/


/**
 * Abstract class for all hypertopic objects
 *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @todo Authentication
 **/

abstract class Fr_Zhou_Objects_Hypertopic {
  /**
   * Service URL (which hypertopic service we are using, 
   * e.g. http://www.example.com/)
   * @var string
   */
  public $_serviceUrl;

  /**
   * Hypertopic server response body
   * @var string
   */
  public $_responseBody;

  /**
   * Hypertopic server response status code
   * @var string
   */
  public $_statusCode;

  /**
   * Uri of the hypertopic object
   * @var string
   */
  public $uri;

  /**
   * Construct function
   *
   * @param string $uri URI of the object
   */
  public function __construct($uri) {
    $this->uri = $uri;
  }

  /**
   * Parser URI
   */
  abstract function parseUri();

  /**
   * Generate XML string
   *
   * @return string XML string
   */
  abstract function toXml();

  /**
   * Parse XML string to generate the object
   *
   * @param string $json JSON string
   */
  abstract function parseXml();

  /**
   * Generate JSON string
   *
   * @return string JSON string
   */
  public function toJson() {
    return json_encode ( $this );
  }

  /**
   * Fetch the hypertopic object from hypertopic server.
   *
   * @return boolean
   */
  public function get($username = null,$password = null) {
    $method = Zend_Http_Client::GET;
    $this->_requestNoContent($method,$username,$password);
  }

  /**
   * Put the hypertopic object to hypertopic server.
   *
   * @return boolean
   */
  public function put($username = null,$password = null) {
    $method = Zend_Http_Client::PUT;
    $this->_requestWithContent($method,$username,$password);
  }
  
  /**
   * Put the hypertopic object to hypertopic server.
   *
   * @return boolean
   */
  public function post($username = null,$password = null) {
    $method = Zend_Http_Client::POST;
    $this->_requestWithContent($method,$username,$password);
  }

  /**
   * Fetch the hypertopic object from hypertopic server.
   *
   * @return boolean
   */
  public function delete($username = null,$password = null) {
    $method = Zend_Http_Client::DELETE;
    $this->_requestNoContent($method,$username,$password);
  }
  
  private function _requestNoContent($method, 
                        $username = null,$password = null){
    // $logger = Zend_Registry::get('logger');
    $http = new Zend_Http_Client ( $this->uri, 
        array ('maxredirects' => 0, 'timeout' => 60 ) );
    if($username != null)
      $http->setAuth($username, $password);
    $http->setMethod ( $method );

    $response = $http->request ();
    // $logger->log($method." ".$this->uri, Zend_Log::INFO);
    $this->_responseBody = $response->getBody ();
    $this->_statusCode = $response->getStatus ();
    // $logger->log($this->_statusCode,Zend_Log::DEBUG);
  }
  
  private function _requestWithContent($method, 
                        $username = null,$password = null){
    // $logger = Zend_Registry::get('logger');
    $http = new Zend_Http_Client ( $this->uri, 
        array ('maxredirects' => 0, 'timeout' => 60 ) );
    if($username != null)
      $http->setAuth($username, $password);
      
    $http->setMethod ( $method );
    $http->setRawData($this->toXml(),'text/xml');
    
    // $logger->log($http,Zend_Log::DEBUG);
    $response = $http->request();
    
    // $logger->log($method." ".$this->uri, Zend_Log::INFO);
    // $logger->log($this->toXml(), Zend_Log::INFO);
    $this->_responseBody = $response->getBody();
    $this->_statusCode = $response->getStatus();
    if($this->_statusCode == "201" 
        && $method == Zend_Http_Client::POST)
      $this->uri = $response->getHeader('location');
  }

  /**
   * escape string for XML output
   *
   * @param string $str string to escape
   *
   * @return string
   */
  protected function escapeXML($str) {
    $str = str_replace ( "&", "&amp;", $str );
    $str = str_replace ( "<", "&lt;", $str );
    $str = str_replace ( ">", "&gt;", $str );
    return $str;
  }
  
  /**
   * If the status code is begin with "2", then success
   *
   * @return bool the http request success or not
   */
  public function success(){
    if($this->_statusCode != ""
        && substr($this->_statusCode,0,1) == "2")
      return true;
    else
      return false;
  }
}
?>