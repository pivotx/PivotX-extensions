<?php
/**
 * Translation functions
 */

$PIVOTX['shop']['lang'] = get_default($PIVOTX['config']->get('shop_language'), 'default');
if($PIVOTX['shop']['lang']!='default') {
    if(file_exists($shopbasedir.'/translations/'.$PIVOTX['shop']['lang'].'.php')) {
        include_once($shopbasedir.'/translations/'.$PIVOTX['shop']['lang'].'.php');
        //debug_printr($translations);
        $PIVOTX['shop'][$PIVOTX['shop']['lang']] = $translations;
    }
}


/**
 * This ugly function will translate your (error)messages
 */
function st($unstranslatedstring) {
	global $PIVOTX;
	
	/**
	 * This will do the OOPs thing
	 */
	$oopslabel = 'oops_shop.'. strtolower(str_replace(array(' ','+','\'','"',',','.'), '_', $unstranslatedstring));
	$oopstranslated = oops_text::tr($oopslabel);
	
	if($oopstranslated && !stristr($oopstranslated, 'oops shop ')) {
		return $oopstranslated;
	}

	// If a language is set, see if the string is translated
	if(
	   $PIVOTX['shop']['lang']
	   && is_array($PIVOTX['shop'][$PIVOTX['shop']['lang']])
	   && isset($PIVOTX['shop'][$PIVOTX['shop']['lang']][$unstranslatedstring])
	) {
		// return the translated string
		return $PIVOTX['shop'][$PIVOTX['shop']['lang']][$unstranslatedstring];
	}
	
	// fallback to default pivotx translation
	$translatedstring = __($unstranslatedstring);
	if($translatedstring!=$unstranslatedstring) {
		return $translatedstring;
	}
	// big failure, return the untranslated string
	return $unstranslatedstring;
}
