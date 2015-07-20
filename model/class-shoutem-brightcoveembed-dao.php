<?php
/**
 * This class is designed to work with Brightcove embed markup wrapped in our own shortcode
 */
class ShoutemBrightcoveEmbedDao extends ShoutemDao {
	
	function attach_to_hooks() {
		$this->attach_to_shortcodes();
	}
	
	public function attach_to_shortcodes() {
		remove_shortcode( 'shoutembrightcoveembed');
		add_shortcode( 'shoutembrightcoveembed', array(&$this, 'shortcode_brightcoveembed' ) );
	}

	/**
	 * shoutem brightcove embed shortcode
	 */
	function shortcode_brightcoveembed($atts, $content) {
        extract(shortcode_atts(array(
            'se_visible' => 'true'
        ), $atts ));
        
        if ($se_visible != 'true') {
        	return '';	
        }

        $dom = new DOMDocument();
		// supress warnings caused by HTML5 tags
		@$dom->loadHTML('<?xml encoding="UTF-8">'.$content);
		$xpath = new DOMXPath($dom);
		$brightcove_node = $xpath->query("//*[contains(concat(' ', @class, ' '),' brightcove-embed ')]")->item(0);
		if (!$brightcove_node) {
			return '';
		}

		$paramIsVid = $xpath->query("//param[@name='isVid']", $brightcove_node)->item(0);
		if (!$paramIsVid || $paramIsVid->getAttribute('value') !== 'true') {
			exit($paramIsVid->getAttribute('value'));
			return '';
		}

		$paramPlayerKey = $xpath->query("//param[@name='playerKey']", $brightcove_node)->item(0);
		$paramVideoPlayer = $xpath->query("//param[@name='@videoPlayer']", $brightcove_node)->item(0);
		if (!$paramPlayerKey || !$paramVideoPlayer) {
			return '';
		}

		$playerKey = $paramPlayerKey->getAttribute('value');
		$videoPlayer = $paramVideoPlayer->getAttribute('value');

		$paramSecureHTML = $xpath->query("//param[@name='secureHTMLConnections']", $brightcove_node)->item(0);
		$secureHTML = $paramSecureHTML && $paramSecureHTML->getAttribute('value') === 'true';
		$paramPubCode = $xpath->query("//param[@name='pubCode']", $brightcove_node)->item(0);
		$pubCode = $paramPubCode && $paramPubCode->getAttribute('value');
		
		$url = 'http://c.brightcove.com/services';
		if ($pubCode) {
			$url = $pubCode.'.ariessaucetown.local/services';
			if ($secureHTML) {
				$url = 'https://secure.'.$url;
			}
			else {
				$url = 'http://c.'.$url;
			}
		} else if ($secureHTML) {
			$url = 'https://secure.brightcove.com/services';
		}

		$url .= '/viewer/htmlFederated?';
		$url .= '&isVid=true';
		$url .= '&playerKey='.$playerKey;
		$url .= '&'.urlencode('@videoPlayer').'='.$videoPlayer;

		$paramWidth = $xpath->query("//param[@name='width']", $brightcove_node)->item(0);
		$paramHeight = $xpath->query("//param[@name='height']", $brightcove_node)->item(0);

		$out = '<object>'.'<embed src="'.$url.'"';
		if ($paramWidth && $paramHeight) {
			$out .= ' width="'.$paramWidth->getAttribute('value').'"';
			$out .= ' height="'.$paramHeight->getAttribute('value').'"';
		}
		$out .= '></embed></object>';
		return $out;
	}

} 
?>
