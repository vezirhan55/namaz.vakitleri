<?php
	
	/* 
	 * SAINTX » Namaz Vakitleri
	 * 
	 * @author: Ogün KARAKUŞ
	 * @web: http://saintx.net
	 * @mail: im@saintx.net
	 * @createdAt: 28.08.2013
	 * @lastUpdate: 29.08.2013
	 */
	
	class NAMAZ_VAKITLERI {
		const AUTHOR = 'SAINTX';
		const CLASS_VERSION = '1.0.0';
		const POST_URI = 'http://www.diyanet.gov.tr/turkish/namazvakti/vakithes_namazsonuc.asp';
		const POST_REFERER_URI = 'http://www.diyanet.gov.tr/turkish/namazvakti/vakithes_namazvakti.asp';
		const POST_PARAMS = 'sehirler=%s&R1=%s&buton=Hesapla&ulk=%s';
		
		protected $ulke = null;
		protected $sehir = null;
		protected $mod = null;
		protected $vakitler = null;
		protected $saveDirectory = null;
		protected $results = null;
		
		public function __construct($ulke = '', $sehir = '', $mod = 'haftalik') {
			if(is_array($ulke)) {
				$this->ulke = $ulke['ulke'];
				$this->sehir = $ulke['sehir'];
				$this->mod = $ulke['mod'];
			} else if(!empty($ulke))
				$this->ulke = $ulke;
			
			if(!empty($sehir))
				$this->sehir = $sehir;
			
			if(!empty($mod))
				$this->mod = $mod;
			
			$this->setVakitler();
		}
		
		public function setUlke($ulke = '') {
			$this->ulke = $ulke;
			
			return $this;
		}
		
		public function getUlke() {
			return $this->ulke;
		}
		
		public function setSehir($sehir = '') {
			$this->sehir = $sehir;
			
			return $this;
		}
		
		public function getSehir() {
			return $this->sehir;
		}
		
		public function setMod($mod = '') {
			$this->mod = $mod;
			
			return $this;
		}
		
		public function getMod() {
			return $this->mod;
		}
		
		protected function setVakitler() {
			$this->vakitler = (object) array(
				'default' => (object) array(
					'imsak' => 'İmsak',
					'gunes' => 'Güneş',
					'ogle' => 'Öğle',
					'ikindi' => 'İkindi',
					'aksam' => 'Akşam',
					'yatsi' => 'Yatsı',
					'kible_saati' => 'Kıble Saati'
				),
				'keys' => array(
					'imsak',
					'gunes',
					'ogle',
					'ikindi',
					'aksam',
					'yatsi',
					'kible_saati'
				),
				'values' => array(
					'İmsak',
					'Güneş',
					'Öğle',
					'İkindi',
					'Akşam',
					'Yatsı',
					'Kıble Saati'
				)
			);
			
			return $this;
		}
		
		public function getVakitler() {
			return $this->vakitler;
		}
		
		public function get($mod = '') {
			switch($mod) {
				case 'array': {
					return $this->getResults()->getArray();
				} break;
				case 'object': {
					return $this->getResults()->getObject();
				} break;
				case 'json': {
					return $this->getResults()->getJSON();
				} break;
				default: {
					return $this->getResults()->getObject();
				}
			}
		}
		
		public function getArray() {
			return json_decode(json_encode($this->results), true);
		}
		
		public function getObject() {
			return $this->results;
		}
		
		public function getJSON() {
			return json_encode($this->results);
		}
		
		public function getResults() {
			$this->getResult();
			
			return $this;
		}
		
		protected function preparePostParams() {
			return sprintf(NAMAZ_VAKITLERI::POST_PARAMS, strtoupper($this->getSehir()), strtoupper($this->getMod()), strtoupper($this->getUlke()));
		}
		
		protected function prepareTime($time, $is_unix = false, $date = '') {
			if(!$is_unix) {
				$time = explode(' ', $time);
				$time[0] = (strlen($time[0]) >= 2) ? $time[0] : '0'.$time[0];
				
				return implode(':', $time);
			} else {
				$time = explode(' ', $time);
				$time[0] = (strlen($time[0]) >= 2) ? $time[0] : '0'.$time[0];
				$time = implode(':', $time);
				
				return strtotime(sprintf('%s %s', $date, $time));
			}
		}
		
		protected function prepareTimes($times, $date) {
			$return_arr = array();
			
			foreach($times as $time) {
				$return_arr[] = (object) array(
					'default' => $this->prepareTime($time),
					'unix' => $this->prepareTime($time, true, $date)
				);
			}
			
			return $return_arr;
		}
		
		protected function prepareResult($datas) {
			$return_arr = array();
			
			unset($datas[0]);
			
			$datas = array_values($datas);
			
			foreach($datas as $data) {
				$times = preg_match_all('#\<td(.*?)\>(.*?)\<\/td\>#si', $data, $matches) ? array_map(array($this, 'trim'), end($matches)) : '';
				$date = $times[0];
				
				unset($times[0]);
				
				$times = $this->prepareTimes(array_values($times), $date);
				
				$return_arr[] = array(
					'tarih' => $date,
					'imsak' => $times[0],
					'gunes' => $times[1],
					'ogle' => $times[2],
					'ikindi' => $times[3],
					'aksam' => $times[4],
					'yatsi' => $times[5],
					'kible_saati' => $times[6]
				);
			}
			
			return $return_arr;
		}
		
		protected function getResult() {
			$data = $this->http_request(
				NAMAZ_VAKITLERI::POST_URI, true, 'windows-1254', 'utf-8', true,
				$this->preparePostParams(), false, NAMAZ_VAKITLERI::POST_REFERER_URI
			)->content;
			
			$data = preg_match('#\<div\salign\=\"center\"\>(.*?)\<\/div\>#si', $data, $matches) ? end($matches) : '';
			
			$_data = preg_match_all('#\<tr\>(.*?)\<\/tr\>#si', $data, $matches) ? $this->prepareResult(end($matches)) : '';
			
			$this->results = $_data;
		}
		
		public function setSaveDirectory($directory = '') {
			$this->saveDirectory = (empty($directory)) ? str_replace('\\', '/', dirname(realpath(__FILE__))) : $directory;
			
			return $this;
		}
		
		public function getSaveDirectory() {
			return $this->saveDirectory;
		}
		
		public function saveResults($filename = '') {
			$handle = fopen(sprintf('%s/%s', $this->getSaveDirectory(), $filename), 'w+');
			
			fwrite($handle, $this->getResults()->getJSON());
			
			fclose($handle);
		}
		
		public function debug() {
			echo '<pre>';
			print_r($this);
			echo '</pre>';
		}
		
		public function trim($string) {
			return str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    ', '&nbsp;'), '', trim($string));
		}
		
		public function http_request($url, $iconv = false, $iconv_in_charset = null, $iconv_out_charset = null, $post = false, $post_fields = null, $ajax = false, $referer = null) {
			$curl = curl_init();
			$data = array('content' => '', 'errno' => '', 'err_msg' => '', 'info' => '');
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_NOBODY, false);
			curl_setopt($curl, CURLOPT_COOKIESESSION, true);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; rv:18.0) Gecko/20100101 Firefox/18.0');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_VERBOSE, true);
			
			if($ajax)
				curl_setopt($curl, CURLOPT_HTTPHEADER, array(
					'X-Requested-With: XMLHttpRequest',
					'Content-Type: application/x-www-form-urlencoded'
				));
			
			if(!is_null($referer))
				curl_setopt($curl, CURLOPT_REFERER, $referer);
			
			if($post) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields);
			}
			
			$data['content'] = ($iconv) ? iconv($iconv_in_charset, $iconv_out_charset, curl_exec($curl)) : curl_exec($curl);
			$data['post_params'] = $post_fields;
			$data['errno'] = curl_errno($curl);
			$data['err_msg'] = curl_error($curl);
			$data['info'] = curl_getinfo($curl);
			curl_close($curl);
			return (object) $data;
		}
	}
	