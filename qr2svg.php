<?php

//
//		qr2svg
//		Convert QR Code into a scalable vector format
//
//		Author: Marc Qualie
//		Source: http://www.marcqualie.com/projects/qr2svg
//

class qr2svg
{
	
	public static function convert ($file, $color = '000')
	{
		
		// Create new intsance
		$svg = new qr2svg_instance();
		
		// Get Image Instance
		if (!file_exists($file)) return $svg->error('Can\'t find source file');
		
		// Get sizes
		$ext = end(explode('.', strtolower($file)));
		$size = getimagesize($file);
		$width = $size[0];
		$height = $size[1];
		$svg->width_original = $width;
		$svg->height_original = $height;
		self::debug('Size:', $width, $height);
		
		// Convert to better quality internal resource
		if ($ext === 'png') $img = imagecreatefrompng($file);
		else if ($ext === 'jpg') $img = imagecreatefromjpeg($file);
		else return $svg->error('Invalid Image Format');
		$source = imagecreatetruecolor($width, $height);
		imagecopy($source, $img, 0, 0, 0, 0, $width, $height);
		
		// Loop through and get grid size
		$x_start = 0;
		$x_end = 0;
		$y_start = 0;
		$y_end = 0;
		for ($y = 0; $y < $height; $y++)
		{
			for ($x = 0; $x < $height; $x++)
			{
				$c = imagecolorat($source, $x, $y);
				$v = $c < 1000;
				if ($v && !$x_start) $x_start = $x;
				if ($v && !$y_start) $y_start = $y;
				if ($v && $x > $x_end) $x_end = $x;
				if ($v && $y > $y_end) $y_end = $y;
			}
		}
		$x_end++;
		$y_end++;
		
		// Build new source from
		$real_width = $width - $x_start - ($width - $x_end);
		$real_height = $height - $y_start - ($height - $y_end);
		$dest = imagecreatetruecolor($real_width, $real_height);
		imagecopyresized($dest, $source, 0, 0, $x_start, $y_start, $real_width, $real_height, $real_width, $real_height);
		
		// Calculate Block Size
		for ($x = 0; $x < $real_width; $x++)
		{
			$c = imagecolorat($dest, $x, 0);
			if ($c > 1000)
			{
				$block_size = $x / 7;
				break;
			}
		}
		self::debug('Real:', $x_start, $y_start, $x_end, $y_end, $real_width, $real_height);
		
		// Build SVG Data Array
		$data = array(
			'color' => $color,
			'rows' => ceil($real_height / $block_size),
			'cols' => ceil($real_width / $block_size),
			'points' => array()
		);
		for ($x = 0; $x < $data['cols']; $x ++)
		{
			for ($y = 0; $y < $data['rows']; $y++)
			{
				$rx = $x * $block_size;
				$ry = $y * $block_size;
				$c = imagecolorat($dest, $rx , $ry);
				$v = $c < 1000 ? true : false;
				if ($v) {
					$data['points'][] = array($x, $y);
				}
			}
		}
		self::debug('Block:', $block_size, ' - ', $data['cols'], 'x', $data['rows']);
		
		// Output
		//if ($ext === 'png') imagejpeg($dest, str_replace('.png', '.jpg', $file), 100);
		//else if ($ext === 'jpg') imagepng($dest, str_replace('.jpg', '.png', $file), 9);
		return $svg->set_data($data);
		
	}
	
	// Debugging
	public static function debug ()
	{
		return;
		echo "<div style='font-family:monospace'>" . implode(' ', func_get_args()) . "</div>";
	}
	
}

//
//	Instance for manipulation later
//
class qr2svg_instance
{
	
	public $width = 0;
	public $width_original = 0;
	public $height = 0;
	public $height_original = 0;
	public $points = array();
	
	private $data;
	
	public function set_data ($data)
	{
		$this->data = $data;
		return $this;
	}
	
	public function save ($file)
	{
		
		// Vars
		if (!$this->data) return;
//		$lb = "\n";
		$this->width = 0 ? $this->data['cols'] + 4 : $this->width_original;
		$this->height = 0 ? $this->data['rows'] + 4 : $this->height_original;
		$xml_points = '';
		
		// Better point system
		$points = array();
		foreach ($this->data['points'] as $a)
		{
			if (!$points[$a[0]]) $points[$a[0]] = array();
			$points[$a[0]][$a[1]] = true;
		}
		$this->points = &$points;
		
		// Build Complex data array
		for ($y = 0; $y < $this->data['rows']; $y++)
		{
			
			$width = 0;
			$sx = -1;
			
			for ($x = 0; $x < $this->data['cols']; $x++)
			{
				if (1)
				{
					if ($points[$x][$y])
					{
						$width++;
						if ($sx < 0) $sx = $x;
					}
					if (
						(!$points[$x][$y] && $width > 0)
						|| $x == $this->data['cols'] - 1
					)
					{
						$xml_points .= '<rect x="' . ($sx + 2) . '"'
							. ' y="' . ($y + 2) . '"'
							. ' width="' . $width . '"'
							. ' height="1"'
							. '/>' . $lb;
						$width = 0;
						$sx = -1;
					}
				}
				else if ($points[$x][$y])
				{
					$xml_points .= '<rect x="' . ($x + 2) . '"'
						. ' y="' . ($y + 2) . '"'
						. ' width="1px"'
						. ' height="1"'
						. '/>' . $lb;
				}
			}
		}
		
		// Save SVG File
		$xml = '<?xml version="1.0" standalone="no"?>'
			. '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'
			. '<svg width="' . $this->width . '" height="' . $this->height . '" viewBox="0 0 ' . ($this->data['cols'] + 4) . ' ' . ($this->data['rows'] + 4) . '" xmlns="http://www.w3.org/2000/svg" version="1.1">'
				. '<defs>' . $lb
				. '<style type="text/css"><![CDATA[ ' . $lb
     			. 'rect{' . $lb
				. 'fill:' . $this->data['color'] . '' . $lb
				. '}' . $lb
				. ']]></style>' . $lb
				. '</defs>' . $lb
				. '<title>qr2svg</title>' . $lb
				. '<desc>SVG generated from QR - http://www.marcqualie.com/projects/qr2svg</desc>' . $lb
				. $xml_points
			. '</svg>';
		file_put_contents($file, $xml);
		
		return $this;
	}
	
	public function error ($str)
	{
		echo "<div><b>[ERROR]</b> {$str}</div>";
		return $this;
	}
	
}