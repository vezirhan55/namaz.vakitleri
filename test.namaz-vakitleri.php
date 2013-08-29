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
	
	header('Content-Type: text/html; charset=utf-8');
	
	require('class.namaz-vakitleri.php');
	
	$NamazVakitleri = new NAMAZ_VAKITLERI;
#	$NamazVakitleri = new NAMAZ_VAKITLERI('turkiye', 'akhisar', 'haftalik');
	
	$NamazVakitleri->setUlke('turkiye')
				   ->setSehir('akhisar')
				   ->setMod('haftalik'); # ('haftalik', 'aylik')
	
	$NamazVakitleri->get('json');
	
	$NamazVakitleri->setSaveDirectory()->saveResults('akhisar-namaz-vakitleri.json');