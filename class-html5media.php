<?php
/**
 * HTML5Media plugin for zenphoto
 * just drop into your plugins directory
 *
 * built from class-textobject
 * @author Richard Laskey (rlaskey.org)
 * @package plugins
 */

$plugin_is_filter = 9|CLASS_PLUGIN;
$plugin_description = gettext('HTML5 video and audio handling.');
$plugin_author = 'Richard Laskey (rlaskey.org)';
$plugin_version = '1.0.0';

# audio
addPluginType('ogg','HTML5Media');
addPluginType('oga','HTML5Media');
addPluginType('mp3','HTML5Media');
addPluginType('m4a','HTML5Media');
addPluginType('wma','HTML5Media');
# video
addPluginType('ogv','HTML5Media');
addPluginType('mp4','HTML5Media');
addPluginType('m4v','HTML5Media');
addPluginType('mov','HTML5Media');
addPluginType('3gp','HTML5Media');
addPluginType('flv','HTML5Media');
addPluginType('mpg','HTML5Media');
addPluginType('avi','HTML5Media');
addPluginType('wmv','HTML5Media');


class HTML5Media extends _Image {

	/* corresponding to HTML5 tags and their file extensions.
	 * yes, I'm way over-doing it in terms of what browser actually support.
	 * a blanked out <video> tag is not the end of the world, however. */
	public $mediaTypes = array(
		'audio' => array('ogg','oga','mp3','m4a','wma'),
		'video' => array('ogv','mp4','m4v','mov','3gp','flv','mpg','avi','wmv')
	);

	/**
	 * @param object $album the owner album
	 * @param string $filename the filename of the media file
	 */
	function __construct(&$album, $filename) {
		global $_zp_supported_images;

		// $album is an Album object; it should already be created.
		if ( ! is_object($album)) return NULL;
		if ( ! $this->classSetup($album, $filename)) { // spoof attempt
			$this->exists = FALSE;
			return;
		}
		$this->sidecars = $_zp_supported_images;
		$this->objectsThumb = checkObjectsThumb($album->localpath, $filename);
		// Check if the file exists.
		if ( ! file_exists($this->localpath) || is_dir($this->localpath)) {
			$this->exists = FALSE;
			return;
		}

		/* check the file extension, and then set the mediaType if supported */
		$extension = substr(strtolower(strrchr($this->localpath,'.')),1);
		foreach ($this->mediaTypes AS $t => $l) {
			if (in_array($extension,$l)) {
				$this->mediaType = $t;
				break;
			}
		}
		unset($t,$l);

		$this->updateDimensions();

		if (parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id), 'filename', FALSE, FALSE)) {
			$this->set('mtime', $ts = filemtime($this->localpath));
			$this->updateMetaData();
			$this->save();
			zp_apply_filter('new_image', $this);
		}
	}

	/**
	 * Required template function
	 *
	 * @return string replacement for the <img /> normally inserted by template
	 */
	function getBody()
	{
		/* the result here is wrapped around a <div><strong><a>
		 * so, we are closing those tags and starting new ones */
		$uri = $this->getFullImage();
		return 
			'</a></strong>'.
			(isset($this->mediaType) ? '<'.$this->mediaType.' src="'.$uri.
			'" controls="true"></'.$this->mediaType.'>'
			: '').
			'</div>'.
			'<p>'.
			'<a href="'.$uri.'">Right-click, download file</a>.'.
			'</p>'.
			'<div><strong><a>';
	}


	/**
	 * @param string $alt Alt text for the url
	 * @param int $size size
	 * @param int $width width
	 * @param int $height height
	 * @param int $cropw crop width
	 * @param int $croph crop height
	 * @param int $cropx crop x axis
	 * @param int $cropy crop y axis
	 * @param string $class Optional style class
	 * @param string $id Optional style id
	 * @param bool $thumbStandin set to true to treat as thumbnail
	 * @param bool $effects ignored
	 * @return string
	 */
	function getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy, $thumbStandin = FALSE, $effects = NULL) {
		return $this->getBody();
	}

	function getSizedImage($size) {return $this->getBody();}


	/**
	 * @param string $path override path
	 * @return string filesystem path, for internal processing
	 */
	function getThumbImageFile($path = NULL) {
		if (is_null($path)) {$path = SERVERPATH;}
		if ($this->objectsThumb != NULL) {
			$imgfile = getAlbumFolder().$this->album->name.'/'.$this->objectsThumb;
		} else {
			/* use a small filmstrip JPG in ZP;
			 * check if it's in the current theme, first */
			$img = 'movie.jpg';
			$imgfile = $path.'/'.THEMEFOLDER.'/'.
				internalToFilesystem($this->album->gallery->getCurrentTheme()).
				'/images/'.$img;
			if ( ! file_exists($imgfile)) {
				$imgfile = $path.'/plugins/'.substr(basename(__FILE__), 0, -4).
					'/'.$img;
			}
		}
		return $imgfile;
	}


	/**
	 * @param string $type 'image' or 'album'
	 * @return string the URL for the thumbnail image
	 */
	function getThumb($type='image') {
		return WEBPATH.'/zp-core/images/movie.jpg';
	}

	function updateDimensions() {
		$this->set('width', getOption('image_size'));
		$this->set('height', getOption('image_size'));
	}
}
