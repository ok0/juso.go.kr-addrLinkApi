/**
 * @author jonghun Yoon(https://github.com/ok0/javascript-pagination)
 */
class AddrSearch extends CI_Controller {
	
	private $_apiKey = NULL;
	private $_countPerPage = NULL;
	
	private $_curl = Array(
		"curl" => NULL
		, "server" => NULL
		, "header" => Array()
	);
	
	private $_req = NULL;
	
	// 생성자
	public function __construct() {
		$this->_setApiKey();
		$this->_setCurlConfig("server", "http://www.juso.go.kr/addrlink/addrLinkApi.do");
		$this->_initCurl();
	}
	
	public function __destruct() {
		$this->_closeCurl();
	}
	
	private function _setApiKey() {
		$this->_apiKey = "PUT YOUR API KEY";
	}
	
	private function _refreshCurl($_method) {
		$this->_closeCurl();
		$this->_initCurl();
	}
	
	private function _closeCurl() {
		$ch = $this->_getCurl("curl");
		if( $ch !== NULL ) {
			curl_close($ch);
		}
	}
	
	private function _initCurl() {
		$issetObj = $this->_getCurl("server");
		
		if( isset($issetObj) ) {
			$this->_setCurlConfig("curl", curl_init());
			curl_setopt($this->_getCurl("curl"), CURLOPT_POST, true);
			curl_setopt($this->_getCurl("curl"), CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->_getCurl("curl"), CURLOPT_HTTPHEADER, $this->_getCurl("header"));
		} else {
			return FALSE;
		}
	}
	
	private function _setCurlConfig($_key, $_val) {
		if( array_key_exists($_key, $this->_curl) && isset($_val) ) {
			$this->_curl[$_key] = $_val;
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	private function _getCurl($_key) {
		if( isset($_key) ) {
			return $this->_curl[$_key];
		} else {
			return $this->_curl;
		}
	}
	
	private function _requestCurl($_data) {
		curl_setopt($this->_getCurl("curl"), CURLOPT_URL, $this->_getCurl("server"));
		curl_setopt($this->_getCurl("curl"), CURLOPT_TIMEOUT, 10);
		curl_setopt($this->_getCurl("curl"), CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->_getCurl("curl"), CURLOPT_POST, TRUE);
		curl_setopt($this->_getCurl("curl"), CURLOPT_POSTFIELDS, $_data);
		
		$result = curl_exec($this->_getCurl("curl"));
		$resultInfo = curl_getinfo($this->_getCurl("curl"));
		
		if( $resultInfo["http_code"] == "200" ) {
			$decodedResult = json_decode($result, TRUE);
			if( $decodedResult["results"]["common"]["errorCode"] == "0" ) {
				return $decodedResult;
			}
		} else {
			return FALSE;
		}
	}
	
	private function _setReq($keyword, $currentPage, $countPerPage) {
		$this->_req = Array(
			"confmKey" => $this->_apiKey
			, "currentPage" => $currentPage
			, "countPerPage" => $countPerPage
			, "keyword" => $keyword
			, "resultType" => "json"
		);
	}
	
	private function _getReq() {
		return $this->_req;
	}
	
	public function replaceKeyword($keyword) {
		$sqlInjection = Array(
			"/[%=><\[\]]/"
			, "/OR/i" , "/SELECT/i" , "/INSERT/i"
			, "/DELETE/i", "/UPDATE/i" , "/CREATE/i"
			, "/DROP/i", "/EXEC/i", "/UNION/i"
			, "/FETCH/i", "/DECLARE/i", "/TRUNCATE/i"
		);
		
		return preg_replace($sqlInjection, "", $keyword);
	}
	
	public function getList($keyword = NULL, $currentPage = NULL, $countPerPage = NULL) {
		if( $keyword != NULL ) {
			$keyword = $this->replaceKeyword($keyword);
			$this->_setReq(
				$keyword
				, ( $currentPage == NULL ) ? 1 : $currentPage
				, ( $countPerPage == NULL ) ? 10 : $countPerPage
			);
			
			$rs = $this->_requestCurl($this->_getReq());
			return $rs;
		} else {
			return FALSE;
		}
	}
	
	// 최상단 결과 하나
	public function get($keyword) {
		$rs = $this->getList($keyword, 1, 1);
		if( $rs["results"]["common"]["totalCount"] > 0 ) {
			return $rs["results"]["juso"][0];
		} else {
			return false;
		}
	}
	
	// 다음페이지
	public function next() {
		$current = $this->_getReq();
		if( $current !== NULL ) {
			return $this->getList($current["keyword"], ($current["currentPage"] + 1), $current["countPerPage"]);
		} else {
			return FALSE;
		}
	}
	
	// 이전페이지
	public function prev() {
		$current = $this->_getReq();
		if( $current !== NULL && $current["currentPage"] > 1 ) {
			return $this->getList($current["keyword"], ($current["currentPage"] - 1), $current["countPerPage"]);
		} else {
			return FALSE;
		}
	}
}
